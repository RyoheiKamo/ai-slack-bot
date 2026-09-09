import { useEffect, useState } from 'react';

import { fetchConversations } from '../../api/conversations';
import { ConversationTable } from '../../components/conversations/ConversationTable';

import type {
    Conversation,
    ConversationListMeta,
} from '../../types/conversation';

export function ConversationListPage() {
  const [conversations, setConversations] = useState<
    Conversation[]
  >([]);

  const [meta, setMeta] =
    useState<ConversationListMeta | null>(null);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState<string | null>(
    null,
  );

  useEffect(() => {
    const loadConversations = async () => {
      try {
        setLoading(true);
        setError(null);

        const response =
          await fetchConversations();

        setConversations(response.data);
        setMeta(response.meta);
      } catch (error) {
        console.error(error);

        setError(
          '会話履歴の取得に失敗しました。',
        );
      } finally {
        setLoading(false);
      }
    };

    loadConversations();
  }, []);

  if (loading) {
    return <p>読み込み中...</p>;
  }

  if (error) {
    return <p>{error}</p>;
  }

  return (
    <main>
      <h1>会話履歴</h1>

      <ConversationTable
        conversations={conversations}
      />

      {meta && (
        <p>
          全{meta.total}件
        </p>
      )}
    </main>
  );
}
