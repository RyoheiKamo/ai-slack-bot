import { BrowserRouter, Route, Routes } from 'react-router-dom';

import { ConversationListPage } from './pages/conversations/ConversationListPage';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route
          path="/admin/conversations"
          element={<ConversationListPage />}
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
