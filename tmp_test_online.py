import os
from backend.api.model_routes import TrainPayload, online_train_endpoint


def main() -> None:
    payload = TrainPayload(
        data=[
            [6.1, 22.0, 24.0, 62.0, 15000, 2.0],
            [6.0, 21.5, 23.5, 63.0, 14000, 2.1],
        ]
    )

    try:
        result = online_train_endpoint(payload)
        print("OK:", result)
    except Exception as e:  # noqa: BLE001
        import traceback

        traceback.print_exc()


if __name__ == "__main__":
    main()

