"""StudyTrack Math Service — motor matemático interno (FastAPI + SymPy).

Endpoints:
- GET  /health     healthcheck
- POST /grade      correção de resposta (numeric | symbolic)
- POST /generate   geração de variante a partir de template + spec de parâmetros
- POST /solve      resolve equação para a variável informada
- POST /evaluate   avalia expressão com variáveis substituídas (retorna True/False ou valor)

Rede interna Docker; todos os POSTs exigem o header X-Math-Token.
"""
from __future__ import annotations

import os
from typing import Literal

from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from .generation import answer_latex, fill_template, sample_parameters
from .grading import (
    DEFAULT_TOLERANCE,
    MathParseError,
    grade_numeric,
    grade_symbolic,
    parse_expression,
    solve_equation,
)

app = FastAPI(title="StudyTrack Math Service", version="0.1.0")

_MATH_TOKEN = os.environ.get("MATH_SERVICE_TOKEN", "")


def require_token(x_math_token: str = Header(default="")) -> None:
    if not _MATH_TOKEN or x_math_token != _MATH_TOKEN:
        raise HTTPException(status_code=401, detail="Unauthorized")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


class GradeRequest(BaseModel):
    mode: Literal["numeric", "symbolic"] = "numeric"
    student_answer: str = Field(min_length=1, max_length=2000)
    expected_expression: str = Field(min_length=1, max_length=2000)
    variables: list[str] = Field(default_factory=list)
    tolerance: float = DEFAULT_TOLERANCE


class GradeResponse(BaseModel):
    correct: bool
    student_latex: str
    expected_latex: str
    feedback: str


@app.post("/grade", dependencies=[Depends(require_token)])
def grade(req: GradeRequest) -> GradeResponse:
    try:
        if req.mode == "numeric":
            correct, expected_latex, student_latex = grade_numeric(
                req.student_answer,
                req.expected_expression,
                req.variables,
                req.tolerance,
            )
        else:
            correct, expected_latex, student_latex = grade_symbolic(
                req.student_answer,
                req.expected_expression,
                req.variables,
            )
    except MathParseError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    return GradeResponse(
        correct=correct,
        student_latex=student_latex,
        expected_latex=expected_latex,
        feedback="Resposta correta!" if correct else "Resposta incorreta.",
    )


class GenerateRequest(BaseModel):
    template: str = Field(default="", max_length=4000)
    answer_expression: str = Field(default="", max_length=2000)
    parameters_spec: dict[str, dict] = Field(default_factory=dict)
    seed: int | None = None


class GenerateResponse(BaseModel):
    parameters: dict[str, int | float]
    prompt: str
    answer_expr: str
    answer_latex: str


@app.post("/generate", dependencies=[Depends(require_token)])
def generate(req: GenerateRequest) -> GenerateResponse:
    try:
        params = sample_parameters(req.parameters_spec, req.seed)
    except MathParseError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    return GenerateResponse(
        parameters=params,
        prompt=fill_template(req.template, params),
        answer_expr=fill_template(req.answer_expression, params),
        answer_latex=answer_latex(req.answer_expression, params),
    )


class SolveRequest(BaseModel):
    expression: str = Field(min_length=1, max_length=2000)
    variable: str = Field(min_length=1, max_length=20)


class SolveResponse(BaseModel):
    solutions: list[str]
    solution_latex: str


@app.post("/solve", dependencies=[Depends(require_token)])
def solve(req: SolveRequest) -> SolveResponse:
    try:
        solutions = solve_equation(req.expression, req.variable)
    except MathParseError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    return SolveResponse(solutions=solutions, solution_latex=", ".join(solutions))


class EvaluateRequest(BaseModel):
    expression: str = Field(min_length=1, max_length=2000)
    variables: dict[str, int | float | str] = Field(default_factory=dict)


class EvaluateResponse(BaseModel):
    result: str
    latex: str = ""


@app.post("/evaluate", dependencies=[Depends(require_token)])
def evaluate(req: EvaluateRequest) -> EvaluateResponse:
    """Avalia uma expressão após substituir as variáveis.

    Expressões booleanas (ex.: ``Eq(x, 3)``) retornam ``True``/``False``;
    expressões numéricas retornam o valor simplificado.
    """
    import sympy as sp

    from sympy.parsing.sympy_parser import convert_equals_signs

    variables = list(req.variables.keys())
    try:
        expr = parse_expression(req.expression, variables)
    except MathParseError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    substitutions = {}
    for name, value in req.variables.items():
        symbol = sp.Symbol(name)
        if isinstance(value, (int, float)):
            substitutions[symbol] = sp.Float(value)
        elif isinstance(value, str):
            try:
                substitutions[symbol] = sp.Float(value)
            except ValueError:
                substitutions[symbol] = sp.Symbol(value)

    try:
        evaluated = sp.simplify(expr.subs(substitutions))
    except Exception as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    if evaluated is sp.true or evaluated is sp.false or isinstance(evaluated, bool):
        result = "True" if evaluated in (sp.true, True) else "False"
    else:
        try:
            result = str(evaluated.evalf())
        except Exception:
            result = str(evaluated)

    return EvaluateResponse(result=result, latex=sp.latex(evaluated))
