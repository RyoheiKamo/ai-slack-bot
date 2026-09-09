import { BrowserRouter, Route, Routes } from "react-router-dom";
import { ConversationDetailPage } from "./pages/conversations/ConversationDetailPage";
import { ConversationListPage } from "./pages/conversations/ConversationListPage";

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/conversations" element={<ConversationListPage />} />
        <Route
          path="/admin/conversations/:id"
          element={<ConversationDetailPage />}
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
