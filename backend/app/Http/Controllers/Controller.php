<?php

namespace App\Http\Controllers;

/**
 * Controller base abstrato da aplicação.
 *
 * Todas as controllers do projeto estendem esta classe. Ela serve como ponto de partida
 * para a hierarquia de controladores e pode receber lógica compartilhada no futuro.
 * Controllers específicos utilizam traits (ex: HasApiResponse) para padronizar respostas.
 */
abstract class Controller {}
