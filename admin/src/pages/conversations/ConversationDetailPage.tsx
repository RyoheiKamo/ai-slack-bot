import { useEffect, useState } from "react";
import { useLocation, useNavigate, useParams } from "react-router-dom";
import { fetchConversationDetail } from "../../api/conversations";
import type { ConversationDetail } from "../../types/conversation";
import { formatDateTime } from "../../utils/date";

type ErrorType = "not-found" | "fetch-error" | "invalid-id" | null;

export function ConversationDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [conversation, setConversation] = useState<ConversationDetail | null>(
    null,
  );
  const [loading, setLoading] = useState(true);
  const [errorType, setErrorType] = useState<ErrorType>(null);
  const from = location.state?.from ?? "/admin/conversations";

  useEffect(() => {
    const loadConversation = async () => {
      if (!id) {
        setErrorType("invalid-id");
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        setErrorType(null);

        const response = await fetchConversationDetail(id);

        setConversation(response.data);
      } catch (error) {
        console.error(error);

        if (error instanceof Error && error.message === "NOT_FOUND") {
          setErrorType("not-found");
        } else {
          setErrorType("fetch-error");
        }
      } finally {
        setLoading(false);
      }
    };

    loadConversation();
  }, [id]);

  if (loading) {
    return <p>読み込み中...</p>;
  }

  if (errorType === "not-found") {
    return (
      <main>
        <h1>会話履歴が見つかりません</h1>

        <p>指定された会話履歴は存在しないか、 すでに削除されています。</p>

        <button type="button" onClick={() => navigate(from)}>
          一覧へ戻る
        </button>
      </main>
    );
  }

  if (errorType === "fetch-error") {
    return (
      <main>
        <h1>エラー</h1>

        <p>会話履歴詳細の取得に失敗しました。</p>

        <button type="button" onClick={() => navigate(from)}>
          一覧へ戻る
        </button>
      </main>
    );
  }

  if (errorType === "invalid-id") {
    return (
      <main>
        <h1>エラー</h1>

        <p>会話IDが指定されていません。</p>

        <button type="button" onClick={() => navigate(from)}>
          一覧へ戻る
        </button>
      </main>
    );
  }

  if (!conversation) {
    return <p>会話履歴がありません。</p>;
  }

  return (
    <main>
      <button type="button" onClick={() => navigate(from)}>
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
        <dd>{formatDateTime(conversation.started_at)}</dd>

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

              <small>{formatDateTime(message.message_created_at)}</small>

              <hr />
            </article>
          ))}
        </div>
      )}
    </main>
  );
}
