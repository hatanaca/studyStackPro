import os

import pytest
from fastapi.testclient import TestClient

from app.main import app

TOKEN = "test-token"


@pytest.fixture(scope="module", autouse=True)
def _set_token():
    os.environ["MATH_SERVICE_TOKEN"] = TOKEN
    # Recarrega o token lido no import do módulo
    import app.main as main

    main._MATH_TOKEN = TOKEN
    yield


@pytest.fixture(scope="module")
def client():
    return TestClient(app)


def test_health(client):
    resp = client.get("/health")
    assert resp.status_code == 200
    assert resp.json() == {"status": "ok"}


def test_grade_requires_token(client):
    resp = client.post("/grade", json={})
    assert resp.status_code == 401


def test_grade_wrong_token(client):
    resp = client.post(
        "/grade",
        json={},
        headers={"X-Math-Token": "wrong"},
    )
    assert resp.status_code == 401


def test_grade_numeric_correct(client):
    resp = client.post(
        "/grade",
        json={
            "mode": "numeric",
            "student_answer": "1.414214",
            "expected_expression": "sqrt(2)",
        },
        headers={"X-Math-Token": TOKEN},
    )
    assert resp.status_code == 200
    body = resp.json()
    assert body["correct"] is True
    assert body["feedback"] == "Resposta correta!"
    assert body["expected_latex"] == r"\sqrt{2}"


def test_grade_numeric_incorrect(client):
    resp = client.post(
        "/grade",
        json={
            "mode": "numeric",
            "student_answer": "2.0",
            "expected_expression": "sqrt(2)",
        },
        headers={"X-Math-Token": TOKEN},
    )
    assert resp.status_code == 200
    assert resp.json()["correct"] is False


def test_grade_symbolic_equivalent(client):
    resp = client.post(
        "/grade",
        json={
            "mode": "symbolic",
            "student_answer": "x^2 - 1",
            "expected_expression": "(x - 1)*(x + 1)",
            "variables": ["x"],
        },
        headers={"X-Math-Token": TOKEN},
    )
    assert resp.status_code == 200
    assert resp.json()["correct"] is True


def test_grade_invalid_expression_422(client):
    resp = client.post(
        "/grade",
        json={
            "mode": "symbolic",
            "student_answer": "x +",
            "expected_expression": "x",
        },
        headers={"X-Math-Token": TOKEN},
    )
    assert resp.status_code == 422


def test_generate_deterministic(client):
    payload = {
        "template": "Resolva {{a}}x + {{b}} = 0",
        "answer_expression": "-{{b}}/{{a}}",
        "parameters_spec": {
            "a": {"type": "int", "min": 1, "max": 9},
            "b": {"type": "int", "min": 1, "max": 9},
        },
        "seed": 42,
    }
    headers = {"X-Math-Token": TOKEN}
    r1 = client.post("/generate", json=payload, headers=headers)
    r2 = client.post("/generate", json=payload, headers=headers)
    assert r1.status_code == 200
    assert r1.json() == r2.json()
    body = r1.json()
    assert "{{" not in body["prompt"]
    assert "{{" not in body["answer_expr"]
