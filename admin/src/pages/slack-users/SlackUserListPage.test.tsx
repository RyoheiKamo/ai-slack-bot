import { render, screen } from "@testing-library/react";
import { userEvent } from "@testing-library/user-event";
import { MemoryRouter } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";
import { formatDateTime } from "../../utils/date";
import SlackUserListPage from "./SlackUserListPage";

describe("SlackUserListPage", () => {
  it("displays slack users", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [
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
        new Response(
          JSON.stringify({
            data: [],
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
        new Response(
          JSON.stringify({
            data: [],
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
        new Response(
          JSON.stringify({
            data: [],
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
});
