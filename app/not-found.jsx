export default function NotFound() {
  return (
    <div className="container">
      <div className="header">
        <div className="headerTitle">
          <strong>IngeChat 360°</strong>
          <span>UNEFA Núcleo Miranda · Sede Los Teques</span>
        </div>
      </div>

      <div className="panel">
        <div className="chat">
          <div className="row bot">
            <div>
              <div className="bubble bot">Página no encontrada (404).</div>
              <div className="meta">IngeChat 360°</div>
            </div>
          </div>
        </div>

        <div className="composer">
          <a className="send" href="/" style={{ textDecoration: 'none' }}>
            Volver al chat
          </a>
        </div>
      </div>
    </div>
  );
}
