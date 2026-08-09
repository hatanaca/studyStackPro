from app.generation import answer_latex, fill_template, sample_parameters
from app.grading import MathParseError

import pytest


def test_sample_int_range():
    params = sample_parameters({"a": {"type": "int", "min": 1, "max": 9}}, seed=42)
    assert 1 <= params["a"] <= 9


def test_sample_choice():
    params = sample_parameters({"b": {"type": "choice", "choices": [2, 4, 6]}}, seed=7)
    assert params["b"] in (2, 4, 6)


def test_sample_float():
    params = sample_parameters({"c": {"type": "float", "min": 0.0, "max": 1.0}}, seed=3)
    assert 0.0 <= params["c"] <= 1.0


def test_seed_is_deterministic():
    spec = {"a": {"type": "int", "min": 1, "max": 100}, "b": {"type": "int", "min": 1, "max": 100}}
    assert sample_parameters(spec, seed=123) == sample_parameters(spec, seed=123)
    assert sample_parameters(spec, seed=123) != sample_parameters(spec, seed=456)


def test_fill_template():
    filled = fill_template("Resolva {{a}}x + {{b}} = 0", {"a": 2, "b": 4})
    assert filled == "Resolva 2x + 4 = 0"


def test_invalid_spec_raises():
    with pytest.raises(MathParseError):
        sample_parameters({"a": {"type": "unknown"}}, seed=1)
    with pytest.raises(MathParseError):
        sample_parameters({"a": {"type": "int"}}, seed=1)


def test_answer_latex():
    latex = answer_latex("-{{b}}/{{a}}", {"a": 2, "b": 4})
    assert latex == "-2"
