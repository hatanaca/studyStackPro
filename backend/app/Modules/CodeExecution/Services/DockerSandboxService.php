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
    private const TIMEOUT = 10; // segundos

    private const MEMORY_LIMIT = '128m';

    private const MAX_CODE_LENGTH = 10000;

    /**
     * Executa código em container Docker isolado.
     *
     * @return array{success: bool, output: string, error: string|null, executionTime: int}
     */
    public function run(string $code, string $language): array
    {
        $startTime = microtime(true);

        if (strlen($code) > self::MAX_CODE_LENGTH) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Código excede o limite de '.self::MAX_CODE_LENGTH.' caracteres.',
                'executionTime' => 0,
            ];
        }

        try {
            $result = match ($language) {
                'php', 'laravel' => $this->executePhp($code, $language),
                'sql' => $this->executeSql($code),
                'bash' => $this->executeBash($code),
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
                'error' => 'Erro interno na execução: '.$e->getMessage(),
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
                'docker run --rm --network none --memory=%s --cpus=0.5 --read-only --tmpfs /tmp:size=10m -v %s:/tmp/code.php:ro %s php /tmp/code.php',
                self::MEMORY_LIMIT,
                escapeshellarg($tmpFile),
                $image
            ));

            $process->setTimeout(self::TIMEOUT);
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
                'docker run --rm --network none --memory=%s --cpus=0.5 --read-only --tmpfs /tmp:size=10m -v %s:/tmp/query.sql:ro code-sandbox-sql sqlite3 :memory: < /tmp/query.sql',
                self::MEMORY_LIMIT,
                escapeshellarg($tmpFile)
            ));

            $process->setTimeout(self::TIMEOUT);
            $process->run();

            return $this->parseProcessOutput($process);
        } finally {
            $this->removeTempFile($tmpFile);
        }
    }

    /**
     * Executa script Bash em container Docker.
     * Código é montado via bind mount para evitar injeção de shell.
     */
    private function executeBash(string $code): array
    {
        $tmpFile = $this->createTempFile($code, 'bash');

        try {
            $process = Process::fromShellCommandline(sprintf(
                'docker run --rm --network none --memory=%s --cpus=0.5 --read-only --tmpfs /tmp:size=10m -v %s:/tmp/script.sh:ro code-sandbox-bash bash /tmp/script.sh',
                self::MEMORY_LIMIT,
                escapeshellarg($tmpFile)
            ));

            $process->setTimeout(self::TIMEOUT);
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
                'error' => 'Execução interrompida: timeout de '.self::TIMEOUT.'s excedido.',
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
