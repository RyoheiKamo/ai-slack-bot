import type { SlackChannelListResponse } from "../types/slackChannel";

type FetchSlackChannelsParams = {
  page?: number;
  slackChannelId?: string;
  name?: string;
};

export async function fetchSlackChannels(
  params: FetchSlackChannelsParams = {},
): Promise<SlackChannelListResponse> {
  const searchParams = new URLSearchParams();

  if (params.page) {
    searchParams.set("page", String(params.page));
  }

  if (params.slackChannelId) {
    searchParams.set("slack_channel_id", params.slackChannelId);
  }

  if (params.name) {
    searchParams.set("name", params.name);
  }

  const query = searchParams.toString();

  const response = await fetch(
    `/api/admin/slack-channels${query ? `?${query}` : ""}`,
    {
      headers: {
        Accept: "application/json",
      },
    },
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch Slack channels: ${response.status}`);
  }

  return response.json();
}
