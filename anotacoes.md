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



