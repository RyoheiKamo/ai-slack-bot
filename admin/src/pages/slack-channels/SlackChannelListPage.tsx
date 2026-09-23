import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { fetchSlackChannels } from "../../api/slackChannels";
import SlackChannelTable from "../../components/slack-channels/SlackChannelTable";
import type {
  SlackChannel,
  SlackChannelListMeta,
} from "../../types/slackChannel";

export default function SlackChannelListPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const appliedSlackChannelId = searchParams.get("slack_channel_id") ?? "";
  const appliedName = searchParams.get("name") ?? "";
  const [slackChannelId, setSlackChannelId] = useState(appliedSlackChannelId);
  const [name, setName] = useState(appliedName);
  const [slackChannels, setSlackChannels] = useState<SlackChannel[]>([]);
  const [meta, setMeta] = useState<SlackChannelListMeta | null>(null);
  const page = Number(searchParams.get("page") ?? "1");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);

    fetchSlackChannels({
      page,
      slackChannelId: appliedSlackChannelId,
      name: appliedName,
    })
      .then((response) => {
        setSlackChannels(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        setSlackChannels([]);
        setMeta(null);
        setError("Slackチャンネル一覧の取得に失敗しました。");
      })
      .finally(() => {
        setLoading(false);
      });
  }, [page, appliedSlackChannelId, appliedName]);

  const handleSearch = () => {
    const params = new URLSearchParams();

    if (slackChannelId) {
      params.set("slack_channel_id", slackChannelId);
    }

    if (name) {
      params.set("name", name);
    }

    params.set("page", "1");

    setSearchParams(params);
  };

  const handlePageChange = (newPage: number) => {
    const params = new URLSearchParams(searchParams);

    params.set("page", String(newPage));

    setSearchParams(params);
  };

  const handleClear = () => {
    setSlackChannelId("");
    setName("");

    const params = new URLSearchParams();

    params.set("page", "1");

    setSearchParams(params);
  };

  return (
    <main>
      <h1>Slackチャンネル一覧</h1>

      <div className="search-form">
        <label>
          Slack Channel ID
          <input
            value={slackChannelId}
            onChange={(event) => setSlackChannelId(event.target.value)}
          />
        </label>

        <label>
          Name
          <input
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </label>

        <button type="button" onClick={handleSearch}>
          検索
        </button>

        <button type="button" onClick={handleClear}>
          クリア
        </button>
      </div>

      {loading ? (
        <p>読み込み中...</p>
      ) : error ? (
        <p>{error}</p>
      ) : slackChannels.length === 0 ? (
        <p>該当するSlackチャンネルがありません。</p>
      ) : (
        <>
          <SlackChannelTable slackChannels={slackChannels} />

          {meta && (
            <div className="pagination">
              <span>
                {meta.current_page} / {meta.last_page}
              </span>

              <div className="pagination-buttons">
                <button
                  type="button"
                  disabled={meta.current_page <= 1}
                  onClick={() => handlePageChange(meta.current_page - 1)}
                >
                  前へ
                </button>

                <button
                  type="button"
                  disabled={meta.current_page >= meta.last_page}
                  onClick={() => handlePageChange(meta.current_page + 1)}
                >
                  次へ
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </main>
  );
}
