from fastapi.testclient import TestClient

from python_engine.engine_api import app


def test_health_unauthorized():
    client = TestClient(app)
    response = client.get("/internal/v1/health")
    assert response.status_code == 401
