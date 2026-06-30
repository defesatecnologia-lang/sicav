export function showToast(message, type = "info") {
  const toastContainer =
    document.getElementById("toastContainer") || createToastContainer();

  const toastEl = document.createElement("div");
  toastEl.className = `toast align-items-center text-bg-${
    type === "error" ? "danger" : type === "warning" ? "warning" : "success"
  } border-0`;
  toastEl.setAttribute("role", "alert");
  toastEl.setAttribute("aria-live", "assertive");
  toastEl.setAttribute("aria-atomic", "true");

  const bodyDiv = document.createElement("div");
  bodyDiv.className = "toast-body";
  bodyDiv.textContent = message;

  const dFlex = document.createElement("div");
  dFlex.className = "d-flex";
  dFlex.appendChild(bodyDiv);

  const closeBtn = document.createElement("button");
  closeBtn.type = "button";
  closeBtn.className = "btn-close btn-close-white me-2 m-auto";
  closeBtn.setAttribute("data-bs-dismiss", "toast");

  dFlex.appendChild(closeBtn);
  toastEl.appendChild(dFlex);
  toastContainer.appendChild(toastEl);

  const toast = new bootstrap.Toast(toastEl, {
    autohide: true,
    delay: 4000,
  });

  toast.show();
  toastEl.addEventListener("hidden.bs.toast", () => {
    toastEl.remove();
  });
}

function createToastContainer() {
  const container = document.createElement("div");
  container.id = "toastContainer";
  container.className = "toast-container position-fixed top-0 end-0 p-3";
  container.style.zIndex = "9999";
  document.body.appendChild(container);
  return container;
}

export function showErrorModal(title, message, type = "error") {
  return Swal.fire({
    title: type === "error" ? "❌ " + title : "⚠️ " + title,
    html: message,
    icon: type,
    confirmButtonText: "Entendi",
    confirmButtonColor: type === "error" ? "#d33" : "#f0ad4e",
    allowOutsideClick: true,
    allowEscapeKey: true,
    allowEnterKey: true,
    focusConfirm: true,
    buttonsStyling: true,
    showClass: { popup: "animate__animated animate__fadeInDown" },
    hideClass: { popup: "animate__animated animate__fadeOutUp" },
  });
}

export function showActiveVisitorModal(
  visitorName,
  visitorId,
  conflictType,
  conflictValue
) {
  const conflictText =
    conflictType === "cpf"
      ? `CPF: <strong>${formatCPF(conflictValue)}</strong>`
      : `Placa: <strong>${conflictValue}</strong>`;

  return Swal.fire({
    title: "⚠️ Visitante Já Registrado",
    html: `
      <p>Já existe um visitante ativo com este ${conflictType === "cpf" ? "CPF" : "placa"}:</p>
      <p><strong>${visitorName}</strong> (ID: ${visitorId})</p>
      <p>${conflictText}</p>
      <hr>
      <p class="text-muted">Deseja finalizar a visita atual e iniciar uma nova?</p>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Finalizar e Registrar Novo",
    cancelButtonText: "Ver Registro",
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    reverseButtons: true,
    allowOutsideClick: false,
    allowEscapeKey: false,
  }).then((result) => {
    if (result.dismiss === Swal.DismissReason.cancel) {
      window.open(`?page=listar_visitantes`, "_blank");
    }
    return result.isConfirmed;
  });
}

export function showSuccessModal(title, message) {
  return Swal.fire({
    title: "✅ " + title,
    html: message,
    icon: "success",
    confirmButtonText: "OK",
    confirmButtonColor: "#28a745",
    showClass: { popup: "animate__animated animate__bounceIn" },
  });
}
