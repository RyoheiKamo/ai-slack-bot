import { useEffect, useState } from "react";

import { fetchConversations } from "../../api/conversations";
import { ConversationTable } from "../../components/conversations/ConversationTable";

import type {
  Conversation,
  ConversationListMeta,
} from "../../types/conversation";

export function ConversationListPage() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [meta, setMeta] = useState<ConversationListMeta | null>(null);

  const [page, setPage] = useState(1);

  const [channel, setChannel] = useState("");
  const [threadTs, setThreadTs] = useState("");

  const [searchChannel, setSearchChannel] = useState("");
  const [searchThreadTs, setSearchThreadTs] = useState("");

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadConversations = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await fetchConversations({
          page,
          channel: searchChannel,
          threadTs: searchThreadTs,
        });

        setConversations(response.data);
        setMeta(response.meta);
      } catch (error) {
        console.error(error);
        setError("会話履歴の取得に失敗しました。");
      } finally {
        setLoading(false);
      }
    };

    loadConversations();
  }, [page, searchChannel, searchThreadTs]);

  const handleSearch = () => {
    setPage(1);
    setSearchChannel(channel);
    setSearchThreadTs(threadTs);
  };

  const handleClear = () => {
    setChannel("");
    setThreadTs("");

    setSearchChannel("");
    setSearchThreadTs("");

    setPage(1);
  };

  if (loading) {
    return <p>読み込み中...</p>;
  }

  if (error) {
    return <p>{error}</p>;
  }

  return (
    <main>
      <h1>会話履歴</h1>

      <div>
        <label htmlFor="channel">Channel</label>

        <input
          id="channel"
          type="text"
          value={channel}
          onChange={(event) => {
            setChannel(event.target.value);
          }}
          placeholder="C12345678"
        />

        <label htmlFor="threadTs">Thread TS</label>

        <input
          id="threadTs"
          type="text"
          value={threadTs}
          onChange={(event) => {
            setThreadTs(event.target.value);
          }}
          placeholder="1757390000.123456"
        />

        <button type="button" onClick={handleSearch}>
          検索
        </button>

        <button type="button" onClick={handleClear}>
          クリア
        </button>
      </div>

      <ConversationTable conversations={conversations} />

      {meta && (
        <div>
          <p>
            {meta.current_page} / {meta.last_page} ページ （全{meta.total}件）
          </p>

          <button
            type="button"
            onClick={() => setPage((prev) => prev - 1)}
            disabled={meta.current_page <= 1}
          >
            前へ
          </button>

          <button
            type="button"
            onClick={() => setPage((prev) => prev + 1)}
            disabled={meta.current_page >= meta.last_page}
          >
            次へ
          </button>
        </div>
      )}
    </main>
  );
}
