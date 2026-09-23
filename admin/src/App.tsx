import { BrowserRouter, Route, Routes } from "react-router-dom";
import { ConversationDetailPage } from "./pages/conversations/ConversationDetailPage";
import { ConversationListPage } from "./pages/conversations/ConversationListPage";
import SlackChannelListPage from "./pages/slack-channels/SlackChannelListPage";
import SlackUserListPage from "./pages/slack-users/SlackUserListPage";

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/conversations" element={<ConversationListPage />} />
        <Route
          path="/admin/conversations/:id"
          element={<ConversationDetailPage />}
        />
        <Route
          path="/admin/slack-channels"
          element={<SlackChannelListPage />}
        />
        <Route path="/admin/slack-users" element={<SlackUserListPage />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
