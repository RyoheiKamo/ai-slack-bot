import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { fetchSlackUsers } from "../../api/slackUsers";
import SlackUserTable from "../../components/slack-users/SlackUserTable";
import type { SlackUser, SlackUserListMeta } from "../../types/slackUser";

export default function SlackUserListPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const appliedSlackUserId = searchParams.get("slack_user_id") ?? "";
  const appliedDisplayName = searchParams.get("display_name") ?? "";
  const [slackUserId, setSlackUserId] = useState(appliedSlackUserId);
  const [displayName, setDisplayName] = useState(appliedDisplayName);
  const [slackUsers, setSlackUsers] = useState<SlackUser[]>([]);
  const page = Number(searchParams.get("page") ?? "1");
  const [meta, setMeta] = useState<SlackUserListMeta | null>(null);

  useEffect(() => {
    fetchSlackUsers({
      page,
      slackUserId: appliedSlackUserId,
      displayName: appliedDisplayName,
    }).then((response) => {
      setSlackUsers(response.data);
      setMeta(response.meta);
    });
  }, [page, appliedSlackUserId, appliedDisplayName]);

  const handleSearch = () => {
    const params = new URLSearchParams();

    if (slackUserId) {
      params.set("slack_user_id", slackUserId);
    }

    if (displayName) {
      params.set("display_name", displayName);
    }

    params.set("page", "1");

    setSearchParams(params);
  };

  const handlePageChange = (newPage: number) => {
    const params = new URLSearchParams(searchParams);

    params.set("page", String(newPage));

    setSearchParams(params);
  };

  return (
    <main>
      <h1>Slackユーザー一覧</h1>

      <div className="search-form">
        <label>
          Slack User ID
          <input
            value={slackUserId}
            onChange={(event) => setSlackUserId(event.target.value)}
          />
        </label>

        <label>
          Display Name
          <input
            value={displayName}
            onChange={(event) => setDisplayName(event.target.value)}
          />
        </label>

        <button type="button" onClick={handleSearch}>
          検索
        </button>
      </div>

      <SlackUserTable slackUsers={slackUsers} />

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
    </main>
  );
}
