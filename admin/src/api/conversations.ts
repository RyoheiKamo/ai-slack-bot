import type { ConversationListResponse } from '../types/conversation';

export async function fetchConversations(
  page = 1,
): Promise<ConversationListResponse> {
  const response = await fetch(
    `/api/admin/conversations?page=${page}`,
    {
      headers: {
        Accept: 'application/json',
      },
    },
  );

  if (!response.ok) {
    throw new Error(
      `Failed to fetch conversations: ${response.status}`,
    );
  }

  return response.json();
}
