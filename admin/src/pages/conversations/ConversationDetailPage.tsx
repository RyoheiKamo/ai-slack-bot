import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { fetchConversationDetail } from "../../api/conversations";
import type { ConversationDetail } from "../../types/conversation";

export function ConversationDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [conversation, setConversation] = useState<ConversationDetail | null>(
    null,
  );

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadConversation = async () => {
      if (!id) {
        setError("会話IDが指定されていません。");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setError(null);

        const response = await fetchConversationDetail(id);

        setConversation(response.data);
      } catch (error) {
        console.error(error);

        setError("会話履歴詳細の取得に失敗しました。");
      } finally {
        setLoading(false);
      }
    };

    loadConversation();
  }, [id]);

  if (loading) {
    return <p>読み込み中...</p>;
  }

  if (error) {
    return <p>{error}</p>;
  }

  if (!conversation) {
    return <p>会話履歴がありません。</p>;
  }

  return (
    <main>
      <button type="button" onClick={() => navigate("/admin/conversations")}>
        一覧へ戻る
      </button>

      <h1>会話履歴詳細</h1>

      <dl>
        <dt>ID</dt>
        <dd>{conversation.id}</dd>

        <dt>Channel</dt>
        <dd>{conversation.channel}</dd>

        <dt>Thread TS</dt>
        <dd>{conversation.thread_ts}</dd>

        <dt>Message Count</dt>
        <dd>{conversation.message_count}</dd>

        <dt>Started At</dt>
        <dd>{conversation.started_at ?? "-"}</dd>

        <dt>Latest Message</dt>
        <dd>{conversation.latest_message ?? "-"}</dd>
      </dl>

      <h2>Messages</h2>

      {conversation.messages.length === 0 ? (
        <p>メッセージがありません。</p>
      ) : (
        <div>
          {conversation.messages.map((message) => (
            <article key={message.id}>
              <p>
                <strong>{message.role}</strong>
              </p>

              <p>{message.content}</p>

              <small>{message.message_created_at}</small>

              <hr />
            </article>
          ))}
        </div>
      )}
    </main>
  );
}
