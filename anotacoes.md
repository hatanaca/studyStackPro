Arquiteturas 

_Event-Driven
    arquitetura onde sistema reague a eventos em vez de executar tudo de forma direta e acoplada, event -> components listen -> react
    * processos assìnscronos
    * integração entre serviços
    * baixo acoplamento

_CQRS 
    CQRS (Command Query Responsibility Segregation) padrá arquitetural que separa as operações de leitura 'queries' das operações de escrita 'commands' em um sistema
    * Command -> Create, Update, Delete
    * Query -> Read, Select
    * Vatagens -> escalabilidade, modelos otimizados, performance, segurança, complexidade gerenciavel;
    * CQRS TOTAL=> banco separado para leitura e escrita;
    CQRS Parcial=> Controllers de leitura,escrita e services diferentes




Design Pattern
    
_Repository Pattern
    Abstrai o acesso ao banco de dados, controller não acessa Eloquent diretamente 'banco', exemplos:
    * User::where('email', $email)->first();
    * $userRepository->findByEmail($email);


_Service Layer
    camada intermediaria entre Controller e Models/Repositories que contem toda a lógica de negócio da aplicação
    * utilizado em arquitetura limpa e Domain-Driven Design



Padrão de Modelagem

_DTO
    Data Transfer Objects, utilizado para transportar dados em camadas
    * Vatangens=> Tipagem, Seg, Clareza, facilidade em testes



Estratégia arquitetural

_API VERSIONADA
    /api/v1/users
    /api/v2/users

    Accept: application/vnd.myapp.v2+json

Tecnologias FrontEnd

_Vue, Typescript, 

_Pinia
biblioteca de gerenciamento de estado  'state management' para aplicações feitas com Vue.js
Estado => dados que afetam a interface, Pinia ferramente para gerenciar estado dos componentes

_Vue Router
biblioteca oficial de rotas do Vue.js
Responsável por mudar url, conteudo porém pagina não recarrega

_ApexCharts (vue3-apexcharts)
Biblioteca utilizada para criar gráficos no frontend. 

_Socket.IO <- no lado do cliente, no lado do server -> Socket.IO
biblioteca que permite comunicação em tempo real


Tecnologias rede

_Nginx 
Proxy reverso

_SSL Termination 
Nginx retira criptografia do https e encaminha http pro backend 

_Rate Limiting 
técnica que limita quantas requisições o usuario consegue realizar por minuto, podendo ser empregada por laravel ou nginx 

_Static files 
Arquivos estáticos entregues pelo servidor 

WebSocket Upgrade
cliente envia http com campo upgrade, servidor responde e inicia uma comunicação bidirecional



BackEnd 

Routes - 
    * cada Request chama uma rota
web.php => rotas acessadas via browser, sessão, cookies, csrf
api.php => retorna json, dados pro front, autenticação Sanctum, JWD
channels.php => chat, notificação eventos echo + web socket 
console.php => comandos artisan 

Classes
    \Route -> endpoints, mapear url -> controller
    \Broadcast -> trabalha autorizar canais, trabalhar com broadcast 
    \DB - > banco de dados 
    \Redis - > cache/filas 
    \facades\emailGenerico -> facades são atalhos para serviçoes no service container
* Facades => camada de abstração que fornece interface para serviços do container e injeção de dep
* aliases de namespace => Facades

* Broadcast::routes(['middleware' => ['auth:sanctum']]);
      Broadcast => Facade => atalho para classe \Illuminate\Support\Facades\Broadcast\
      ::route() => método estático da classe
   cada arquivo de rota pode devolver algo como http,json, logica,wenhooks, comandos p/ testes


  O trait é um molde de resposta — garante que toda a API fale a mesma língua, independente de qual controller está respondendo.
  Um Trait é um bloco de métodos reutilizáveis que pode ser "injetado" em qualquer classe. Como o PHP não tem herança múltipla, Traits resolvem esse problema:

      




            






















