"""Parsing e correção de respostas matemáticas via SymPy.

Segurança: entradas não confiáveis são parseadas com parse_expr em um
global_dict restrito (sem builtins, sem acesso ao Python), com limite de
tamanho e rejeição de padrões perigosos (``__``, ``;``). Nunca usa eval/exec.
"""
from __future__ import annotations

import re

import sympy as sp
from sympy.parsing.sympy_parser import (
    convert_equals_signs,
    convert_xor,
    implicit_multiplication_application,
    parse_expr,
    standard_transformations,
)

MAX_INPUT_LENGTH = 2000

# Transformações amigáveis para resposta de estudante: 2x -> 2*x, 2(x+1) -> 2*(x+1), x^2 -> x**2
_TRANSFORMATIONS = standard_transformations + (
    implicit_multiplication_application,
    convert_xor,
)

# Somente nomes SymPy "seguros" ficam disponíveis; __builtins__ fica vazio.
_SAFE_GLOBAL_DICT = {
    name: getattr(sp, name)
    for name in dir(sp)
    if not name.startswith("_")
}
_SAFE_GLOBAL_DICT["__builtins__"] = {}

_DANGEROUS_PATTERN = re.compile(r"__|\bimport\b|\bexec\b|\beval\b|;")

DEFAULT_TOLERANCE = 1e-6


class MathParseError(ValueError):
    """Entrada inválida ou não parseável."""


def parse_expression(raw: str, variables: list[str] | None = None) -> sp.Basic:
    """Converte texto do estudante/template em expressão SymPy, de forma segura."""
    if not isinstance(raw, str) or not raw.strip():
        raise MathParseError("Entrada vazia.")
    if len(raw) > MAX_INPUT_LENGTH:
        raise MathParseError("Entrada muito longa.")
    if _DANGEROUS_PATTERN.search(raw):
        raise MathParseError("Entrada inválida.")

    local_dict = {}
    if variables:
        local_dict = {name: sp.Symbol(name) for name in variables}

    try:
        expr = parse_expr(
            raw,
            local_dict=local_dict,
            global_dict=_SAFE_GLOBAL_DICT,
            transformations=_TRANSFORMATIONS,
            evaluate=True,
        )
    except Exception as exc:  # SympifyError/TokenError/SyntaxError/TypeError/...
        raise MathParseError("Expressão inválida.") from exc

    return expr


def _latex(expr: sp.Basic) -> str:
    try:
        return sp.latex(expr)
    except Exception:
        return ""


def solve_equation(raw: str, variable: str) -> list[str]:
    """Resolve a equação (aceita 'x^2 - 4 = 0' ou 'x^2 - 4') e retorna as soluções em LaTeX."""
    symbol = sp.Symbol(variable)
    transformations = _TRANSFORMATIONS + (convert_equals_signs,)

    try:
        expr = parse_expr(
            raw,
            local_dict={variable: symbol},
            global_dict=_SAFE_GLOBAL_DICT,
            transformations=transformations,
            evaluate=True,
        )
        solutions = sp.solve(expr, symbol)
    except MathParseError:
        raise
    except Exception as exc:
        raise MathParseError("Não foi possível resolver a equação.") from exc

    return [_latex(sp.simplify(s)) for s in solutions]


def grade_numeric(
    student_answer: str,
    expected_expression: str,
    variables: list[str] | None = None,
    tolerance: float = DEFAULT_TOLERANCE,
) -> tuple[bool, str, str]:
    """Compara resposta numérica com o valor esperado (tolerância relativa/absoluta)."""
    expected = parse_expression(expected_expression, variables)
    try:
        student_num = float(student_answer)
    except ValueError:
        student_num = float(parse_expression(student_answer, variables).evalf())

    expected_num = float(expected.evalf())
    scale = max(1.0, abs(expected_num))
    correct = abs(student_num - expected_num) <= tolerance * scale

    return correct, _latex(expected), f"{student_num:.10g}"


def grade_symbolic(
    student_answer: str,
    expected_expression: str,
    variables: list[str] | None = None,
) -> tuple[bool, str, str]:
    """Verifica equivalência algébrica: simplify(student - expected) == 0."""
    student = parse_expression(student_answer, variables)
    expected = parse_expression(expected_expression, variables)

    correct = sp.simplify(student - expected) == 0
    if not correct:
        try:
            correct = sp.expand(student - expected) == 0
        except Exception:
            correct = False

    return correct, _latex(expected), _latex(student)
