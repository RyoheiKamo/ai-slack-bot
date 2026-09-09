import type {
  ConversationDetail,
  ConversationListResponse,
} from "../types/conversation";

type FetchConversationParams = {
  page?: number;
  channel?: string;
  threadTs?: string;
};

export async function fetchConversations({
  page = 1,
  channel = "",
  threadTs = "",
}: FetchConversationParams): Promise<ConversationListResponse> {
  const params = new URLSearchParams({
    page: String(page),
  });

  if (channel) {
    params.set("channel", channel);
  }

  if (threadTs) {
    params.set("thread_ts", threadTs);
  }

  const response = await fetch(
    `/api/admin/conversations?${params.toString()}`,
    {
      headers: {
        Accept: "application/json",
      },
    },
  );

  if (!response.ok) {
    throw new Error(`Failed to fetch conversations: ${response.status}`);
  }

  return response.json();
}

export async function fetchConversationDetail(
  id: string,
): Promise<{ data: ConversationDetail }> {
  const response = await fetch(`/api/admin/conversations/${id}`, {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch conversation detail: ${response.status}`);
  }

  return response.json();
}
