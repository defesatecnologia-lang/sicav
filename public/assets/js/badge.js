import { escapeHtml } from "./utils.js";

export function imprimirCracha() {
  const content = document.getElementById("badgeModalContent");
  if (!content) return;

  const printWindow = window.open("", "_blank", "width=400,height=600");
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Impressão de Crachá</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <style>
        body { padding: 20px; }
        @media print { body { padding: 0; } }
      </style>
    </head>
    <body>${content.innerHTML}</body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();

  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 1000);
}

export function buildBadgeHtml(data, photoUrl) {
  const visitTypeMap = {
    prestador: "Prestador de Serviço",
    visitante: "Visitante",
    entrega: "Entrega",
    outro: "Outro",
  };

  const now = new Date();
  const dateStr = now.toLocaleDateString("pt-BR");
  const timeStr = now.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });

  const cpfFormatted = data.cpf
    ? data.cpf.replace(/\D/g, "").replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
    : "";

  return `
    <div class="badge-template">
      <h4><i class="fas fa-id-card"></i> Crachá de Visitante</h4>
      <hr>
      ${photoUrl ? `<img src="${escapeHtml(photoUrl)}" alt="Foto do Visitante" class="u-cracha-photo">` : `<div class="u-cracha-photo-placeholder d-flex align-items-center justify-content-center bg-light rounded"><i class="fas fa-user fa-3x text-muted"></i></div>`}
      <h3 class="mt-2 u-cracha-name"><strong>${escapeHtml(data.name || "")}</strong></h3>
      <div class="badge-info">
        ${cpfFormatted ? `<p><strong>CPF:</strong> ${escapeHtml(cpfFormatted)}</p>` : ""}
        ${data.company ? `<p><strong>Empresa:</strong> ${escapeHtml(data.company)}</p>` : ""}
        <p><strong>Setor:</strong> ${escapeHtml(data.meet || "")}</p>
        <p><strong>Tipo:</strong> ${escapeHtml(visitTypeMap[data.type] || data.type || "")}</p>
        <p><strong>Data:</strong> ${dateStr}</p>
        <p><strong>Hora:</strong> ${timeStr}</p>
      </div>
    </div>
  `;
}
