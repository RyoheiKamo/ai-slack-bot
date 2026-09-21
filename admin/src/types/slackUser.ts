export type SlackUser = {
  id: number;
  slack_user_id: string;
  display_name: string | null;
  real_name: string | null;
  first_used_at: string | null;
  last_used_at: string | null;
  message_count: number;
  created_at: string;
  updated_at: string;
};

export type SlackUserListMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type SlackUserListResponse = {
  data: SlackUser[];
  meta: SlackUserListMeta;
};
