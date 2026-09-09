export type Conversation = {
  id: number;
  channel: string;
  thread_ts: string;
  message_count: number;
  latest_message: string | null;
  started_at: string | null;
  created_at: string;
  updated_at: string;
};

export type ConversationListMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type ConversationListResponse = {
  data: Conversation[];
  meta: ConversationListMeta;
};

export type ConversationMessage = {
  id: number;
  message_id: string;
  role: string;
  content: string;
  message_created_at: string;
};

export type ConversationDetail = {
  id: number;
  channel: string;
  thread_ts: string;
  message_count: number;
  latest_message: string | null;
  started_at: string | null;
  created_at: string;
  updated_at: string;
  messages: ConversationMessage[];
};
