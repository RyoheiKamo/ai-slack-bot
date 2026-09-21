import type { SlackUser } from "../../types/slackUser";
import { formatDateTime } from "../../utils/date";

type Props = {
  slackUsers: SlackUser[];
};

export default function SlackUserTable({ slackUsers }: Props) {
  return (
    <table>
      <thead>
        <tr>
          <th>Slack User ID</th>
          <th>Display Name</th>
          <th>Real Name</th>
          <th>Message Count</th>
          <th>First Used At</th>
          <th>Last Used At</th>
        </tr>
      </thead>

      <tbody>
        {slackUsers.map((slackUser) => (
          <tr key={slackUser.id}>
            <td>{slackUser.slack_user_id}</td>
            <td>{slackUser.display_name ?? "-"}</td>
            <td>{slackUser.real_name ?? "-"}</td>
            <td>{slackUser.message_count}</td>
            <td>{formatDateTime(slackUser.first_used_at)}</td>
            <td>{formatDateTime(slackUser.last_used_at)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
