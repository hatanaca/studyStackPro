import pytest

from app.grading import (
    MathParseError,
    grade_numeric,
    grade_symbolic,
    parse_expression,
    solve_equation,
)


def test_parse_implicit_multiplication():
    assert parse_expression("2x + 1").equals(sp_expand("2*x + 1"))


def sp_expand(raw: str):
    from sympy import expand

    return expand(parse_expression(raw))


def test_grade_numeric_correct_within_tolerance():
    correct, _, _ = grade_numeric("1.414214", "sqrt(2)")
    assert correct


def test_grade_numeric_wrong():
    correct, _, _ = grade_numeric("3.0", "sqrt(2)")
    assert not correct


def test_grade_numeric_expression_input():
    correct, _, _ = grade_numeric("2*sqrt(2)", "sqrt(8)")
    assert correct


def test_grade_numeric_tolerance_zero():
    correct, _, _ = grade_numeric("1.41421356", "sqrt(2)", tolerance=0.0)
    assert not correct


def test_grade_symbolic_equivalent():
    correct, expected, student = grade_symbolic("x^2 - 1", "(x - 1)*(x + 1)", ["x"])
    assert correct
    assert expected == r"\left(x - 1\right) \left(x + 1\right)"
    assert student == "x^{2} - 1"


def test_grade_symbolic_not_equivalent():
    correct, _, _ = grade_symbolic("x^2 + 1", "(x - 1)*(x + 1)", ["x"])
    assert not correct


def test_grade_symbolic_without_variables_still_works():
    correct, _, _ = grade_symbolic("a^2 - b^2", "(a - b)*(a + b)", ["a", "b"])
    assert correct


def test_grade_symbolic_expression_float_variant():
    correct, _, _ = grade_symbolic("2*(x + 1)", "2*x + 2", ["x"])
    assert correct


def test_invalid_expression_raises():
    with pytest.raises(MathParseError):
        grade_symbolic("not a real expression !!!", "x", ["x"])


def test_empty_input_raises():
    with pytest.raises(MathParseError):
        parse_expression("  ")


def test_code_injection_blocked():
    with pytest.raises(MathParseError):
        parse_expression("__import__('os').system('ls')")
    with pytest.raises(MathParseError):
        parse_expression("x; import os")


def test_overlong_input_raises():
    with pytest.raises(MathParseError):
        parse_expression("x" * 5000)


def test_solve_quadratic_expression():
    solutions = solve_equation("x^2 - 4", "x")
    assert sorted(solutions) == ["-2", "2"]


def test_solve_quadratic_equation_with_equals():
    solutions = solve_equation("x^2 - 4 = 0", "x")
    assert sorted(solutions) == ["-2", "2"]


def test_solve_linear_equation():
    solutions = solve_equation("2x + 4 = 0", "x")
    assert solutions == ["-2"]


def test_solve_unsolvable_raises():
    with pytest.raises(MathParseError):
        solve_equation("sin(x) + cos(x) + x^5 = x", "x")
