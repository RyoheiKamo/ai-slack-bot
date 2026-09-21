import { useEffect, useState } from "react";
import { fetchSlackUsers } from "../../api/slackUsers";
import SlackUserTable from "../../components/slack-users/SlackUserTable";
import type { SlackUser } from "../../types/slackUser";

export default function SlackUserListPage() {
  const [slackUsers, setSlackUsers] = useState<SlackUser[]>([]);

  useEffect(() => {
    fetchSlackUsers().then((response) => {
      setSlackUsers(response.data);
    });
  }, []);

  return (
    <main>
      <h1>Slackユーザー一覧</h1>

      <SlackUserTable slackUsers={slackUsers} />
    </main>
  );
}
