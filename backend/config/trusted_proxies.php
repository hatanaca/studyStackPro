<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configuração de proxies de confiança para aplicar atrás de Nginx / load balancer.
    | Necessário para URL HTTPS, rate limit por IP real e cookies seguros.
    |
    | Valores aceitos:
    |   - '*' : confia em todos os proxies
    |   - string com IPs separados por vírgula: '10.0.0.1,10.0.0.2'
    |   - array de IPs: ['10.0.0.1', '10.0.0.2']
    |
    | Defina via variável de ambiente TRUSTED_PROXIES no .env:
    |   TRUSTED_PROXIES=*
    |   TRUSTED_PROXIES=10.0.0.1,10.0.0.2
    |
    */

    'proxies' => env('TRUSTED_PROXIES', null),

];
