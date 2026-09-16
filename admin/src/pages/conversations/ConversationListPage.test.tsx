import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter, useLocation } from "react-router-dom";
import { afterEach, describe, expect, it, vi } from "vitest";
import { ConversationListPage } from "./ConversationListPage";

function LocationDisplay() {
  const location = useLocation();

  return (
    <div data-testid="location">
      {location.pathname}
      {location.search}
    </div>
  );
}

describe("ConversationListPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("displays conversations", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [
            {
              id: 1,
              channel: "C12345678",
              thread_ts: "1757390000.123456",
              message_count: 2,
              latest_message: "最新のメッセージ",
              started_at: "2026-09-10T01:00:00Z",
              created_at: "2026-09-10T01:00:00Z",
              updated_at: "2026-09-10T01:05:00Z",
            },
          ],
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
        <ConversationListPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText("C12345678")).toBeInTheDocument();

    expect(screen.getByText("1757390000.123456")).toBeInTheDocument();

    expect(screen.getByText("最新のメッセージ")).toBeInTheDocument();

    expect(screen.getByText("2")).toBeInTheDocument();
  });
});

describe("ConversationListPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("filters conversations by channel", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(
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
      <MemoryRouter initialEntries={["/admin/conversations"]}>
        <ConversationListPage />
      </MemoryRouter>,
    );

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalled();
    });

    fireEvent.change(screen.getByLabelText("Channel"), {
      target: {
        value: "C12345678",
      },
    });

    fireEvent.click(
      screen.getByRole("button", {
        name: "検索",
      }),
    );

    await waitFor(() => {
      expect(fetchMock).toHaveBeenLastCalledWith(
        expect.stringContaining("channel=C12345678"),
        expect.anything(),
      );
    });
  });
});

it("filters conversations by thread ts", async () => {
  const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation(() =>
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
    <MemoryRouter initialEntries={["/admin/conversations"]}>
      <ConversationListPage />
    </MemoryRouter>,
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenCalled();
  });

  fireEvent.change(screen.getByLabelText("Thread TS"), {
    target: {
      value: "1757390000.123456",
    },
  });

  fireEvent.click(
    screen.getByRole("button", {
      name: "検索",
    }),
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenLastCalledWith(
      expect.stringContaining("thread_ts=1757390000.123456"),
      expect.anything(),
    );
  });
});

it("filters conversations by channel and thread ts", async () => {
  const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation(() =>
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
    <MemoryRouter initialEntries={["/admin/conversations"]}>
      <ConversationListPage />
    </MemoryRouter>,
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenCalled();
  });

  fireEvent.change(screen.getByLabelText("Channel"), {
    target: {
      value: "C12345678",
    },
  });

  fireEvent.change(screen.getByLabelText("Thread TS"), {
    target: {
      value: "1757390000.123456",
    },
  });

  fireEvent.click(
    screen.getByRole("button", {
      name: "検索",
    }),
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenLastCalledWith(
      expect.stringContaining("channel=C12345678"),
      expect.anything(),
    );

    expect(fetchMock).toHaveBeenLastCalledWith(
      expect.stringContaining("thread_ts=1757390000.123456"),
      expect.anything(),
    );
  });
});

it("moves to the next page", async () => {
  const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation(() =>
    Promise.resolve(
      new Response(
        JSON.stringify({
          data: [],
          meta: {
            current_page: 1,
            last_page: 2,
            per_page: 20,
            total: 25,
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
    <MemoryRouter initialEntries={["/admin/conversations?page=1"]}>
      <ConversationListPage />
    </MemoryRouter>,
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenCalled();
  });

  fireEvent.click(
    screen.getByRole("button", {
      name: "次へ",
    }),
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenLastCalledWith(
      expect.stringContaining("page=2"),
      expect.anything(),
    );
  });
});

it("moves to the previous page", async () => {
  const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation(() =>
    Promise.resolve(
      new Response(
        JSON.stringify({
          data: [],
          meta: {
            current_page: 2,
            last_page: 2,
            per_page: 20,
            total: 25,
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
    <MemoryRouter initialEntries={["/admin/conversations?page=2"]}>
      <ConversationListPage />
    </MemoryRouter>,
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenCalled();
  });

  fireEvent.click(
    screen.getByRole("button", {
      name: "前へ",
    }),
  );

  await waitFor(() => {
    expect(fetchMock).toHaveBeenLastCalledWith(
      expect.stringContaining("page=1"),
      expect.anything(),
    );
  });
});

it("navigates to conversation detail when row is clicked", async () => {
  vi.spyOn(globalThis, "fetch").mockImplementation(() =>
    Promise.resolve(
      new Response(
        JSON.stringify({
          data: [
            {
              id: 10,
              channel: "C12345678",
              thread_ts: "1757390000.123456",
              message_count: 2,
              latest_message: "最新のメッセージ",
              started_at: "2026-09-10T01:00:00Z",
              created_at: "2026-09-10T01:00:00Z",
              updated_at: "2026-09-10T01:05:00Z",
            },
          ],
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
    ),
  );

  render(
    <MemoryRouter initialEntries={["/admin/conversations?page=1"]}>
      <ConversationListPage />
      <LocationDisplay />
    </MemoryRouter>,
  );

  const channel = await screen.findByText("C12345678");

  fireEvent.click(channel.closest("tr")!);

  await waitFor(() => {
    expect(screen.getByTestId("location")).toHaveTextContent(
      "/admin/conversations/10",
    );
  });
});
