import type { SlackChannel } from "../../types/slackChannel";
import { formatDateTime } from "../../utils/date";

type Props = {
  slackChannels: SlackChannel[];
};

export default function SlackChannelTable({ slackChannels }: Props) {
  return (
    <table>
      <thead>
        <tr>
          <th>Slack Channel ID</th>
          <th>Name</th>
          <th>First Used At</th>
          <th>Last Used At</th>
        </tr>
      </thead>

      <tbody>
        {slackChannels.map((slackChannel) => (
          <tr key={slackChannel.id}>
            <td>{slackChannel.slack_channel_id}</td>
            <td>{slackChannel.name ?? "-"}</td>
            <td>{formatDateTime(slackChannel.first_used_at)}</td>
            <td>{formatDateTime(slackChannel.last_used_at)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
