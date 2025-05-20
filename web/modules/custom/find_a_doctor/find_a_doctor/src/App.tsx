import { useState } from 'react'
import reactLogo from './assets/react.svg'
import viteLogo from '/vite.svg'
import './App.css';
// import { data } from 'react-router-dom';

function App(
  {
    dataSet,
  }: {
    dataSet: DOMStringMap
  }
) {
  const [count, setCount] = useState(0);
  const initialState = dataSet.initialState;
  const props = initialState ? JSON.parse(initialState) : {};
  console.log('App dataSet:', props);

  return (
    <>
      <div className="fad-header">
        <a href="https://vite.dev" target="_blank">
          <img src={viteLogo} className="fad-logo" alt="Vite logo" />
        </a>
        <a href="https://react.dev" target="_blank">
          <img src={reactLogo} className="fad-logo fad-react" alt="React logo" />
        </a>
      </div>
      <h1>Vite + React</h1>
      <h1 className="fadtw:text-3xl fadtw:font-bold fadtw:underline">
        Hello world!
      </h1>
      <div className="fad-card">
        <button onClick={() => setCount((count) => count + 1)}>
          count is {count}
        </button>
        <p>
          Edit <code>src/App.tsx</code> and save to test HMR
        </p>
      </div>
      <p className="fad-read-the-docs">
        Click on the Vite and React logos to learn more
      </p>
    </>
  )
}

export default App
