from pathlib import Path
from typing import List, Optional

import numpy as np
from fastapi import Body, FastAPI, HTTPException
from pydantic import BaseModel

from backend.ml.online_autoencoder import (
    ARTIFACT_DIR,
    ReplayBuffer,
    create_model,
    initial_train,
    load_buffer,
    load_model,
    online_train_step,
    reconstruction_error,
    save_buffer,
    save_model,
)


app = FastAPI(title="Magalface Online Autoencoder API")


class TrainPayload(BaseModel):
    data: List[List[float]]
    epochs: Optional[int] = None
    batch_size: Optional[int] = None


class ErrorPayload(BaseModel):
    data: List[List[float]]


def _to_numpy_2d(data: List[List[float]]) -> np.ndarray:
    arr = np.asarray(data, dtype=np.float32)
    if arr.ndim != 2:
        raise ValueError("Os dados devem ser uma matriz 2D: [[...], [...], ...].")
    if arr.shape[0] < 1 or arr.shape[1] < 1:
        raise ValueError("Os dados devem conter pelo menos 1 amostra e 1 feature.")
    return arr


@app.post("/api/model/initial-train")
def initial_train_endpoint(payload: TrainPayload = Body(...)):
    """
    Treinamento inicial offline do autoencoder.
    - Self-supervised: entrada = alvo.
    - Infere input_dim a partir dos dados recebidos.
    """
    try:
        X = _to_numpy_2d(payload.data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    input_dim = X.shape[1]
    model, optimizer, criterion = create_model(input_dim=input_dim)

    epochs = payload.epochs if payload.epochs is not None else 20
    batch_size = payload.batch_size if payload.batch_size is not None else 64

    initial_train(model, optimizer, criterion, X_init=X, epochs=epochs, batch_size=batch_size)

    # Persistir modelo e buffer vazio (ou inicializado com os dados)
    save_model(model)
    buffer = ReplayBuffer(max_size=5000)
    buffer.add_batch(X)
    save_buffer(buffer)

    return {"status": "ok", "input_dim": input_dim, "samples": int(X.shape[0])}


@app.post("/api/model/online-train")
def online_train_endpoint(payload: TrainPayload = Body(...)):
    """
    Treinamento online:
    - Carrega modelo e ReplayBuffer.
    - Adiciona novos dados ao buffer (que descarta amostras antigas).
    - Treina algumas épocas apenas com os dados presentes no buffer.
    """
    model_path = ARTIFACT_DIR / "autoencoder.pt"
    if not model_path.exists():
        raise HTTPException(status_code=400, detail="Modelo ainda não foi treinado. Use /api/model/initial-train primeiro.")

    try:
        X_new = _to_numpy_2d(payload.data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    input_dim = X_new.shape[1]
    try:
        model = load_model(input_dim=input_dim, path=model_path)
    except ValueError as e:
        # Erros de compatibilidade de dimensão do modelo são retornados como 400
        raise HTTPException(status_code=400, detail=str(e))

    _model_unused, optimizer, criterion = create_model(input_dim=input_dim)

    buffer = load_buffer()

    epochs = payload.epochs if payload.epochs is not None else 3
    batch_size = payload.batch_size if payload.batch_size is not None else 64

    try:
        online_train_step(model, optimizer, criterion, buffer, X_new=X_new, epochs=epochs, batch_size=batch_size)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    save_model(model, path=model_path)
    save_buffer(buffer)

    return {"status": "ok", "added_samples": int(X_new.shape[0]), "buffer_size": int(buffer.get_array().shape[0])}


@app.post("/api/model/reconstruction-error")
def reconstruction_error_endpoint(payload: ErrorPayload = Body(...)):
    """
    Calcula o erro de reconstrução por amostra para os dados fornecidos.
    Pode ser usado como score de anomalia.
    """
    model_path = ARTIFACT_DIR / "autoencoder.pt"
    if not model_path.exists():
        raise HTTPException(status_code=400, detail="Modelo ainda não foi treinado.")

    try:
        X = _to_numpy_2d(payload.data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    input_dim = X.shape[1]
    model = load_model(input_dim=input_dim, path=model_path)

    try:
        errors = reconstruction_error(model, X)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    return {"errors": errors.tolist()}
