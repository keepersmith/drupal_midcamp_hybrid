import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

const root = document.getElementById('find-a-doctor-root') as HTMLElement;
if (!root) {
  throw new Error('Root element not found');
}
const dataSet = root.dataset;

createRoot(root).render(
  <StrictMode>
    <App
      dataSet={dataSet}
    />
  </StrictMode>,
)
