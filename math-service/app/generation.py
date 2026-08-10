"""Geração de variantes de exercícios a partir de templates parametrizados."""
from __future__ import annotations

import random

import sympy as sp

from .grading import MathParseError, parse_expression


def sample_parameters(spec: dict[str, dict], seed: int | None = None) -> dict[str, int | float]:
    """Amostra valores para cada parâmetro conforme a spec (int|float|choice)."""
    rng = random.Random(seed)
    params: dict[str, int | float] = {}
    for name, cfg in spec.items():
        kind = cfg.get("type", "int")
        try:
            if kind == "int":
                params[name] = rng.randint(int(cfg["min"]), int(cfg["max"]))
            elif kind == "float":
                params[name] = rng.uniform(float(cfg["min"]), float(cfg["max"]))
            elif kind == "choice":
                params[name] = rng.choice(cfg["choices"])
            else:
                raise MathParseError(f"Tipo de parâmetro desconhecido: {kind}")
        except (KeyError, TypeError, ValueError) as exc:
            raise MathParseError(f"Spec inválida para o parâmetro '{name}'.") from exc
    return params


def fill_template(template: str, params: dict[str, int | float]) -> str:
    """Substitui placeholders {{name}} pelos valores amostrados."""
    filled = template
    for name, value in params.items():
        filled = filled.replace("{{" + name + "}}", _format_value(value))
    return filled


def answer_latex(answer_expression: str, params: dict[str, int | float]) -> str:
    """LaTeX da resposta correta com os parâmetros substituídos."""
    try:
        expr = parse_expression(fill_template(answer_expression, params))
        return sp.latex(expr)
    except MathParseError:
        return ""


def _format_value(value: int | float) -> str:
    if isinstance(value, float):
        return f"{value:.6g}"
    return str(value)
