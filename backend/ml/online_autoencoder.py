import os
import pickle
from collections import deque
from pathlib import Path
from typing import Optional, Tuple

import numpy as np
import torch
from torch import nn
from torch.utils.data import DataLoader, TensorDataset


class Autoencoder(nn.Module):
    """
    Autoencoder totalmente conectado para dados tabulares.
    Self-supervised: a saída tenta reconstruir a própria entrada.
    """

    def __init__(self, input_dim: int) -> None:
        super().__init__()
        self.encoder = nn.Sequential(
            nn.Linear(input_dim, 64),
            nn.ReLU(),
            nn.Linear(64, 16),
            nn.ReLU(),
        )
        self.decoder = nn.Sequential(
            nn.Linear(16, 64),
            nn.ReLU(),
            nn.Linear(64, input_dim),
        )

    def forward(self, x: torch.Tensor) -> torch.Tensor:
        z = self.encoder(x)
        out = self.decoder(z)
        return out


def create_model(input_dim: int, lr: float = 1e-3) -> Tuple[Autoencoder, torch.optim.Optimizer, nn.Module]:
    """
    Cria o autoencoder, otimizador Adam e critério MSE self-supervised.
    """
    model = Autoencoder(input_dim=input_dim)
    optimizer = torch.optim.Adam(model.parameters(), lr=lr, weight_decay=1e-5)
    criterion = nn.MSELoss()
    return model, optimizer, criterion


class ReplayBuffer:
    """
    Buffer de replay implementando "esquecimento" via deque(maxlen).
    Apenas as amostras mais recentes são mantidas na memória.
    """

    def __init__(self, max_size: int = 5000) -> None:
        self.max_size = int(max_size)
        self._buffer: deque[np.ndarray] = deque(maxlen=self.max_size)

    def add_batch(self, x_batch: np.ndarray) -> None:
        """
        Adiciona um batch de dados ao buffer (cada linha = 1 amostra).
        """
        if x_batch is None:
            return
        x_batch = np.asarray(x_batch)
        if x_batch.ndim != 2:
            raise ValueError("x_batch deve ser um array 2D [batch_size, input_dim].")

        for row in x_batch:
            self._buffer.append(np.asarray(row, dtype=np.float32))

    def get_array(self) -> Optional[np.ndarray]:
        """
        Retorna todos os dados atuais do buffer como array [N, input_dim],
        ou None se estiver vazio.
        """
        if not self._buffer:
            return None
        return np.stack(self._buffer, axis=0)


def _make_loader(x: np.ndarray, batch_size: int) -> DataLoader:
    tensor = torch.as_tensor(x, dtype=torch.float32)
    dataset = TensorDataset(tensor)
    return DataLoader(dataset, batch_size=batch_size, shuffle=True)


def initial_train(
    model: nn.Module,
    optimizer: torch.optim.Optimizer,
    criterion: nn.Module,
    X_init: np.ndarray,
    epochs: int = 20,
    batch_size: int = 64,
) -> None:
    """
    Treinamento inicial offline do autoencoder.
    Self-supervised: alvo = própria entrada.
    """
    X_init = np.asarray(X_init, dtype=np.float32)
    if X_init.ndim != 2:
        raise ValueError("X_init deve ser array 2D [N, input_dim].")
    loader = _make_loader(X_init, batch_size=batch_size)

    model.train()
    for _ in range(int(epochs)):
        for (batch,) in loader:
            optimizer.zero_grad()
            recon = model(batch)
            loss = criterion(recon, batch)
            loss.backward()
            optimizer.step()


def online_train_step(
    model: nn.Module,
    optimizer: torch.optim.Optimizer,
    criterion: nn.Module,
    replay_buffer: ReplayBuffer,
    X_new: np.ndarray,
    epochs: int = 3,
    batch_size: int = 64,
) -> None:
    """
    Treino online:
    - Adiciona X_new ao ReplayBuffer (que descarta amostras antigas via maxlen).
    - Executa poucas épocas de treino usando SOMENTE os dados atuais do buffer.
    Isso implementa o modo de esquecimento focando nos dados recentes.
    """
    X_new = np.asarray(X_new, dtype=np.float32)
    if X_new.ndim != 2:
        raise ValueError("X_new deve ser array 2D [n_new, input_dim].")

    replay_buffer.add_batch(X_new)
    X_buf = replay_buffer.get_array()
    if X_buf is None:
        return

    loader = _make_loader(X_buf, batch_size=batch_size)

    model.train()
    for _ in range(int(epochs)):
        for (batch,) in loader:
            optimizer.zero_grad()
            recon = model(batch)
            loss = criterion(recon, batch)
            loss.backward()
            optimizer.step()


def reconstruction_error(model: nn.Module, X: np.ndarray) -> np.ndarray:
    """
    Calcula o erro de reconstrução MSE por amostra.
    Pode ser usado como score de anomalia / qualidade.
    """
    X = np.asarray(X, dtype=np.float32)
    if X.ndim != 2:
        raise ValueError("X deve ser array 2D [N, input_dim].")

    model.eval()
    with torch.no_grad():
        x_tensor = torch.as_tensor(X, dtype=torch.float32)
        recon = model(x_tensor)
        mse_per_sample = torch.mean((recon - x_tensor) ** 2, dim=1)
    return mse_per_sample.cpu().numpy()


# Persistência de modelo e buffer
ARTIFACT_DIR = Path(__file__).resolve().parent / "artifacts"
ARTIFACT_DIR.mkdir(parents=True, exist_ok=True)


def save_model(model: nn.Module, path: Optional[os.PathLike] = None) -> Path:
    """
    Salva apenas o state_dict do modelo em ARTIFACT_DIR.
    """
    if path is None:
        path = ARTIFACT_DIR / "autoencoder.pt"
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    torch.save(model.state_dict(), path)
    return path


def load_model(input_dim: int, path: Optional[os.PathLike] = None) -> Autoencoder:
    """
    Recria a arquitetura do autoencoder e carrega o state_dict salvo.
    """
    if path is None:
        path = ARTIFACT_DIR / "autoencoder.pt"
    path = Path(path)
    model = Autoencoder(input_dim=input_dim)
    if path.exists():
        state = torch.load(path, map_location="cpu")
        try:
            model.load_state_dict(state)
        except RuntimeError as e:
            # Erros de shape (dimens��o incompat��vel) s��o convertidos em ValueError
            raise ValueError(
                f"Dimensao de entrada incompativel entre o modelo salvo e os dados atuais: {e}"
            ) from e
    return model


def save_buffer(replay_buffer: ReplayBuffer, path: Optional[os.PathLike] = None) -> Path:
    """
    Persiste o ReplayBuffer via pickle.
    """
    if path is None:
        path = ARTIFACT_DIR / "replay_buffer.pkl"
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("wb") as f:
        pickle.dump(replay_buffer, f)
    return path


def load_buffer(path: Optional[os.PathLike] = None, max_size: int = 5000) -> ReplayBuffer:
    """
    Carrega o ReplayBuffer salvo ou cria um novo vazio.
    """
    if path is None:
        path = ARTIFACT_DIR / "replay_buffer.pkl"
    path = Path(path)
    if path.exists():
        with path.open("rb") as f:
            buf = pickle.load(f)
        if isinstance(buf, ReplayBuffer):
            return buf
    return ReplayBuffer(max_size=max_size)
