import { render, screen } from "@testing-library/react";
import { userEvent } from "@testing-library/user-event";
import { MemoryRouter } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";
import { formatDateTime } from "../../utils/date";
import SlackUserListPage from "./SlackUserListPage";

const slackUserData = [
  {
    id: 1,
    slack_user_id: "U12345678",
    display_name: "ryohei",
    real_name: "Ryohei Kamo",
    first_used_at: "2026-09-01T10:00:00+09:00",
    last_used_at: "2026-09-17T10:00:00+09:00",
    message_count: 5,
    created_at: "2026-09-01T10:00:00+09:00",
    updated_at: "2026-09-17T10:00:00+09:00",
  },
];

const createSlackUserResponse = ({
  data = slackUserData,
  currentPage = 1,
  lastPage = 1,
  total = data.length,
}: {
  data?: typeof slackUserData;
  currentPage?: number;
  lastPage?: number;
  total?: number;
} = {}): Response =>
  new Response(
    JSON.stringify({
      data,
      meta: {
        current_page: currentPage,
        last_page: lastPage,
        per_page: 20,
        total,
      },
    }),
    {
      status: 200,
      headers: {
        "Content-Type": "application/json",
      },
    },
  );

describe("SlackUserListPage", () => {
  it("displays slack users", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(createSlackUserResponse());

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText("U12345678")).toBeInTheDocument();

    expect(screen.getByText("ryohei")).toBeInTheDocument();

    expect(screen.getByText("5")).toBeInTheDocument();

    expect(screen.getByText("Ryohei Kamo")).toBeInTheDocument();

    expect(
      screen.getByText(formatDateTime("2026-09-01T10:00:00+09:00")),
    ).toBeInTheDocument();

    expect(
      screen.getByText(formatDateTime("2026-09-17T10:00:00+09:00")),
    ).toBeInTheDocument();
  });

  it("filters by slack user id", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          data: [],
          total: 0,
        }),
      ),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    const input = screen.getByLabelText("Slack User ID");

    await userEvent.type(input, "U12345678");

    await userEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("slack_user_id=U12345678"),
      expect.any(Object),
    );
  });

  it("filters by display name", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          data: [],
          total: 0,
        }),
      ),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    const input = screen.getByLabelText("Display Name");

    await userEvent.type(input, "ryohei");

    await userEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("display_name=ryohei"),
      expect.any(Object),
    );
  });

  it("moves to next page", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          currentPage: 1,
          lastPage: 3,
          total: 50,
        }),
      ),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    await userEvent.click(
      await screen.findByRole("button", {
        name: "次へ",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("page=2"),
      expect.any(Object),
    );
  });

  it("moves to previous page", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          currentPage: 2,
          lastPage: 3,
          total: 50,
        }),
      ),
    );

    render(
      <MemoryRouter initialEntries={["/admin/slack-users?page=2"]}>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    await userEvent.click(
      await screen.findByRole("button", {
        name: "前へ",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("page=1"),
      expect.any(Object),
    );
  });

  it("keeps search conditions when moving to next page", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          currentPage: 1,
          lastPage: 3,
          total: 50,
        }),
      ),
    );

    render(
      <MemoryRouter
        initialEntries={[
          "/admin/slack-users?slack_user_id=U123&display_name=ryohei&page=1",
        ]}
      >
        <SlackUserListPage />
      </MemoryRouter>,
    );

    await userEvent.click(
      await screen.findByRole("button", {
        name: "次へ",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("page=2"),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("slack_user_id=U123"),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("display_name=ryohei"),
      expect.any(Object),
    );
  });

  it("clears search conditions", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        createSlackUserResponse({
          data: [],
          total: 0,
        }),
      ),
    );

    render(
      <MemoryRouter
        initialEntries={[
          "/admin/slack-users?slack_user_id=U123&display_name=ryohei&page=2",
        ]}
      >
        <SlackUserListPage />
      </MemoryRouter>,
    );

    expect(screen.getByLabelText("Slack User ID")).toHaveValue("U123");

    expect(screen.getByLabelText("Display Name")).toHaveValue("ryohei");

    await userEvent.click(
      screen.getByRole("button", {
        name: "クリア",
      }),
    );

    expect(screen.getByLabelText("Slack User ID")).toHaveValue("");

    expect(screen.getByLabelText("Display Name")).toHaveValue("");

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("page=1"),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.not.stringContaining("slack_user_id="),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.not.stringContaining("display_name="),
      expect.any(Object),
    );
  });

  it("shows loading state while fetching", async () => {
    let resolveFetch!: (response: Response) => void;

    vi.spyOn(globalThis, "fetch").mockImplementation(
      () =>
        new Promise<Response>((resolve) => {
          resolveFetch = resolve;
        }),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    expect(screen.getByText("読み込み中...")).toBeInTheDocument();

    resolveFetch(
      createSlackUserResponse({
        data: [],
        total: 0,
      }),
    );

    expect(
      await screen.findByText("該当するSlackユーザーがありません。"),
    ).toBeInTheDocument();
  });

  it("shows error message when fetching fails", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(null, {
        status: 500,
      }),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    expect(
      await screen.findByText("Slackユーザー一覧の取得に失敗しました。"),
    ).toBeInTheDocument();
  });

  it("shows empty message when no slack users are found", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      createSlackUserResponse({
        data: [],
        total: 0,
      }),
    );

    render(
      <MemoryRouter>
        <SlackUserListPage />
      </MemoryRouter>,
    );

    expect(
      await screen.findByText("該当するSlackユーザーがありません。"),
    ).toBeInTheDocument();
  });
});
