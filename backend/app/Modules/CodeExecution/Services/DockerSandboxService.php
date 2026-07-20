<?php

namespace App\Modules\CodeExecution\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Serviço de execução em sandbox Docker.
 *
 * Executa código PHP, SQL, Laravel e Bash em containers Docker isolados
 * com restrições de segurança (timeout, memória, rede, filesystem).
 *
 * Segurança: código é escrito em arquivo temporário e montado no container
 * via bind mount, evitando injeção de comandos via argumentos de shell.
 */
class DockerSandboxService
{
    private int $timeout;

    private string $memoryLimit;

    private int $maxCodeLength;

    public function __construct()
    {
        $this->timeout = config('code_execution.timeout', 10);
        $this->memoryLimit = config('code_execution.memory_limit', '128m');
        $this->maxCodeLength = config('code_execution.max_code_length', 10000);
    }

    /**
     * Executa código em container Docker isolado.
     *
     * @return array{success: bool, output: string, error: string|null, executionTime: int}
     */
    public function run(string $code, string $language): array
    {
        $startTime = microtime(true);

        if (strlen($code) > $this->maxCodeLength) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Código excede o limite de '.$this->maxCodeLength.' caracteres.',
                'executionTime' => 0,
            ];
        }

        try {
            $result = match ($language) {
                'php', 'laravel' => $this->executePhp($code, $language),
                'sql' => $this->executeSql($code),
                default => ['success' => false, 'output' => '', 'error' => "Linguagem '{$language}' não suportada no sandbox."],
            };

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            return array_merge($result, ['executionTime' => $executionTime]);
        } catch (\Throwable $e) {
            Log::error('Docker sandbox error', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'output' => '',
                'error' => 'Erro interno na execução do sandbox.',
                'executionTime' => (int) ((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Cria arquivo temporário com o código e retorna o path.
     */
    private function createTempFile(string $code, string $suffix): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), "sandbox_{$suffix}_");
        file_put_contents($tmpFile, $code);

        return $tmpFile;
    }

    /**
     * Remove arquivo temporário de forma segura.
     */
    private function removeTempFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Executa código PHP em container Docker.
     * Código é montado via bind mount para evitar injeção de shell.
     */
    private function executePhp(string $code, string $language): array
    {
        $dockerfile = $language === 'laravel' ? 'php-laravel' : 'php';
        $image = "code-sandbox-{$dockerfile}";
        $tmpFile = $this->createTempFile($code, 'php');

        try {
            $process = Process::fromShellCommandline(sprintf(
                'docker run --rm --network none --memory=%s --cpus=0.5 --read-only --pids-limit 50 --user nobody --security-opt no-new-privileges --tmpfs /tmp:size=10m -v %s:/tmp/code.php:ro %s php /tmp/code.php',
                $this->memoryLimit,
                escapeshellarg($tmpFile),
                $image
            ));

            $process->setTimeout($this->timeout);
            $process->run();

            return $this->parseProcessOutput($process);
        } finally {
            $this->removeTempFile($tmpFile);
        }
    }

    /**
     * Executa SQL em container Docker com SQLite.
     * Código é montado via bind mount para evitar injeção de shell.
     */
    private function executeSql(string $code): array
    {
        $tmpFile = $this->createTempFile($code, 'sql');

        try {
            $process = Process::fromShellCommandline(sprintf(
                'docker run --rm --network none --memory=%s --cpus=0.5 --read-only --pids-limit 50 --user nobody --security-opt no-new-privileges --tmpfs /tmp:size=10m -v %s:/tmp/query.sql:ro code-sandbox-sql sqlite3 :memory: < /tmp/query.sql',
                $this->memoryLimit,
                escapeshellarg($tmpFile)
            ));

            $process->setTimeout($this->timeout);
            $process->run();

            return $this->parseProcessOutput($process);
        } finally {
            $this->removeTempFile($tmpFile);
        }
    }

    /**
     * Parseia output do processo Docker.
     */
    private function parseProcessOutput(Process $process): array
    {
        $output = $process->getErrorOutput() ?: $process->getOutput();
        $exitCode = $process->getExitCode();

        // timeout
        if ($process->getTermSignal() !== null) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Execução interrompida: timeout de '.$this->timeout.'s excedido.',
            ];
        }

        if ($exitCode === 0) {
            return [
                'success' => true,
                'output' => trim($process->getOutput()),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'output' => trim($process->getOutput()),
            'error' => trim($output) ?: "Processo terminou com exit code {$exitCode}.",
        ];
    }
}
