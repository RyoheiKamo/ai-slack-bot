import type { SlackUserListResponse } from "../types/slackUser";

type FetchSlackUsersParams = {
  page?: number;
  slackUserId?: string;
  displayName?: string;
};

export async function fetchSlackUsers(
  params: FetchSlackUsersParams = {},
): Promise<SlackUserListResponse> {
  const searchParams = new URLSearchParams();

  if (params.page) {
    searchParams.set("page", String(params.page));
  }

  if (params.slackUserId) {
    searchParams.set("slack_user_id", params.slackUserId);
  }

  if (params.displayName) {
    searchParams.set("display_name", params.displayName);
  }

  const query = searchParams.toString();

  const response = await fetch(
    `/api/admin/slack-users${query ? `?${query}` : ""}`,
    {
      headers: {
        Accept: "application/json",
      },
    },
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch Slack users: ${response.status}`);
  }

  return response.json();
}
