import { render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router-dom";
import { afterEach, describe, expect, it, vi } from "vitest";
import { ConversationDetailPage } from "./ConversationDetailPage";

describe("ConversationDetailPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("displays conversation detail", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          data: {
            id: 1,
            channel: "C12345678",
            thread_ts: "1757390000.123456",
            message_count: 2,
            latest_message: "Laravelについて説明します。",
            started_at: "2026-09-10T01:00:00Z",
            created_at: "2026-09-10T01:00:00Z",
            updated_at: "2026-09-10T01:05:00Z",
            messages: [
              {
                id: 1,
                message_id: "11111111-1111-1111-1111-111111111111",
                role: "user",
                content: "Laravelについて教えて",
                message_created_at: "2026-09-10T01:00:00Z",
              },
              {
                id: 2,
                message_id: "22222222-2222-2222-2222-222222222222",
                role: "assistant",
                content: "Laravelについて説明します。",
                message_created_at: "2026-09-10T01:00:10Z",
              },
            ],
          },
        }),
        {
          status: 200,
          headers: {
            "Content-Type": "application/json",
          },
        },
      ),
    );

    render(
      <MemoryRouter initialEntries={["/admin/conversations/1"]}>
        <Routes>
          <Route
            path="/admin/conversations/:id"
            element={<ConversationDetailPage />}
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(await screen.findByText("C12345678")).toBeInTheDocument();

    expect(screen.getByText("1757390000.123456")).toBeInTheDocument();

    expect(screen.getByText("2")).toBeInTheDocument();

    expect(screen.getByText("Laravelについて教えて")).toBeInTheDocument();

    expect(screen.getAllByText("Laravelについて説明します。")).toHaveLength(2);

    expect(screen.getByText("user")).toBeInTheDocument();

    expect(screen.getByText("assistant")).toBeInTheDocument();
  });

  it("displays not found message when conversation does not exist", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          message: "Not Found",
        }),
        {
          status: 404,
          headers: {
            "Content-Type": "application/json",
          },
        },
      ),
    );

    render(
      <MemoryRouter initialEntries={["/admin/conversations/999999"]}>
        <Routes>
          <Route
            path="/admin/conversations/:id"
            element={<ConversationDetailPage />}
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(
      await screen.findByRole("heading", {
        name: "会話履歴が見つかりません",
      }),
    ).toBeInTheDocument();

    expect(
      screen.getByText(/指定された会話履歴は存在しないか/),
    ).toBeInTheDocument();

    expect(
      screen.getByRole("button", {
        name: "一覧へ戻る",
      }),
    ).toBeInTheDocument();
  });

  it("displays fetch error message when request fails", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          message: "Internal Server Error",
        }),
        {
          status: 500,
          headers: {
            "Content-Type": "application/json",
          },
        },
      ),
    );

    render(
      <MemoryRouter initialEntries={["/admin/conversations/1"]}>
        <Routes>
          <Route
            path="/admin/conversations/:id"
            element={<ConversationDetailPage />}
          />
        </Routes>
      </MemoryRouter>,
    );

    expect(
      await screen.findByRole("heading", {
        name: "エラー",
      }),
    ).toBeInTheDocument();

    expect(
      screen.getByText("会話履歴詳細の取得に失敗しました。"),
    ).toBeInTheDocument();

    expect(
      screen.getByRole("button", {
        name: "一覧へ戻る",
      }),
    ).toBeInTheDocument();
  });
});
