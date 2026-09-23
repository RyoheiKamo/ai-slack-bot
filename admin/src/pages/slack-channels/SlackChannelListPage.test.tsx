import { render, screen } from "@testing-library/react";
import { userEvent } from "@testing-library/user-event";
import { MemoryRouter } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";
import { formatDateTime } from "../../utils/date";
import SlackChannelListPage from "./SlackChannelListPage";

const slackChannelData = [
  {
    id: 1,
    slack_channel_id: "C12345678",
    name: "development",
    first_used_at: "2026-09-20T10:00:00+09:00",
    last_used_at: "2026-09-22T08:00:00+09:00",
    created_at: "2026-09-20T10:00:00+09:00",
    updated_at: "2026-09-22T08:00:00+09:00",
  },
];

describe("SlackChannelListPage", () => {
  it("displays slack channels", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          data: slackChannelData,
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 1,
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
      <MemoryRouter>
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText("C12345678")).toBeInTheDocument();

    expect(screen.getByText("development")).toBeInTheDocument();

    expect(
      screen.getByText(formatDateTime("2026-09-20T10:00:00+09:00")),
    ).toBeInTheDocument();

    expect(
      screen.getByText(formatDateTime("2026-09-22T08:00:00+09:00")),
    ).toBeInTheDocument();
  });

  it("filters by slack channel id", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        new Response(
          JSON.stringify({
            data: [],
            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter>
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    const input = screen.getByLabelText("Slack Channel ID");

    await userEvent.type(input, "C12345678");

    await userEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("slack_channel_id=C12345678"),
      expect.any(Object),
    );
  });

  it("filters by name", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        new Response(
          JSON.stringify({
            data: [],
            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter>
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    const input = screen.getByLabelText("Name");

    await userEvent.type(input, "development");

    await userEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("name=development"),
      expect.any(Object),
    );
  });

  it("filters by slack channel id", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        new Response(
          JSON.stringify({
            data: [],
            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter>
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    const input = screen.getByLabelText("Slack Channel ID");

    await userEvent.type(input, "C12345678");

    await userEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("slack_channel_id=C12345678"),
      expect.any(Object),
    );
  });

  it("moves to next page", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        new Response(
          JSON.stringify({
            data: slackChannelData,
            meta: {
              current_page: 1,
              last_page: 3,
              per_page: 20,
              total: 50,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter>
        <SlackChannelListPage />
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
        new Response(
          JSON.stringify({
            data: slackChannelData,
            meta: {
              current_page: 2,
              last_page: 3,
              per_page: 20,
              total: 50,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter initialEntries={["/admin/slack-channels?page=2"]}>
        <SlackChannelListPage />
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
        new Response(
          JSON.stringify({
            data: slackChannelData,
            meta: {
              current_page: 1,
              last_page: 3,
              per_page: 20,
              total: 50,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter
        initialEntries={[
          "/admin/slack-channels?slack_channel_id=C123&name=development&page=1",
        ]}
      >
        <SlackChannelListPage />
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
      expect.stringContaining("slack_channel_id=C123"),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("name=development"),
      expect.any(Object),
    );
  });

  it("clears search conditions", async () => {
    vi.spyOn(globalThis, "fetch").mockImplementation(() =>
      Promise.resolve(
        new Response(
          JSON.stringify({
            data: [],
            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
          }),
          {
            status: 200,
            headers: {
              "Content-Type": "application/json",
            },
          },
        ),
      ),
    );

    render(
      <MemoryRouter
        initialEntries={[
          "/admin/slack-channels?slack_channel_id=C123&name=development&page=2",
        ]}
      >
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    expect(screen.getByLabelText("Slack Channel ID")).toHaveValue("C123");

    expect(screen.getByLabelText("Name")).toHaveValue("development");

    await userEvent.click(
      screen.getByRole("button", {
        name: "クリア",
      }),
    );

    expect(screen.getByLabelText("Slack Channel ID")).toHaveValue("");

    expect(screen.getByLabelText("Name")).toHaveValue("");

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.stringContaining("page=1"),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.not.stringContaining("slack_channel_id="),
      expect.any(Object),
    );

    expect(globalThis.fetch).toHaveBeenLastCalledWith(
      expect.not.stringContaining("name="),
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
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    expect(screen.getByText("読み込み中...")).toBeInTheDocument();

    resolveFetch(
      new Response(
        JSON.stringify({
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
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

    expect(
      await screen.findByText("該当するSlackチャンネルがありません。"),
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
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    expect(
      await screen.findByText("Slackチャンネル一覧の取得に失敗しました。"),
    ).toBeInTheDocument();
  });

  it("shows empty message when no slack channels are found", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
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
      <MemoryRouter>
        <SlackChannelListPage />
      </MemoryRouter>,
    );

    expect(
      await screen.findByText("該当するSlackチャンネルがありません。"),
    ).toBeInTheDocument();
  });
});
