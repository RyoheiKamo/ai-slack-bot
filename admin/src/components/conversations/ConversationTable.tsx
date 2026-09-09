import { useNavigate } from "react-router-dom";
import type { Conversation } from "../../types/conversation";

type Props = {
  conversations: Conversation[];
};

export function ConversationTable({ conversations }: Props) {
  const navigate = useNavigate();

  if (conversations.length === 0) {
    return <p>会話履歴がありません。</p>;
  }

  return (
    <table>
      <thead>
        <tr>
          <th>Channel</th>
          <th>Thread TS</th>
          <th>Messages</th>
          <th>Latest Message</th>
          <th>Started At</th>
        </tr>
      </thead>

      <tbody>
        {conversations.map((conversation) => (
          <tr
            key={conversation.id}
            onClick={() => {
              navigate(`/admin/conversations/${conversation.id}`);
            }}
            style={{ cursor: "pointer" }}
          >
            <td>{conversation.channel}</td>
            <td>{conversation.thread_ts}</td>
            <td>{conversation.message_count}</td>
            <td>{conversation.latest_message ?? "-"}</td>
            <td>{conversation.started_at ?? "-"}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
