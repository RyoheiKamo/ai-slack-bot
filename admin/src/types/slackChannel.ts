export type SlackChannel = {
  id: number;
  slack_channel_id: string;
  name: string | null;
  first_used_at: string | null;
  last_used_at: string | null;
  created_at: string;
  updated_at: string;
};

export type SlackChannelListMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type SlackChannelListResponse = {
  data: SlackChannel[];
  meta: SlackChannelListMeta;
};
