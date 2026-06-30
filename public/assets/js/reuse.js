export let currentReusedId = null;

export function limparReutilizacao() {
  currentReusedId = null;
  const info = document.getElementById("reutilizacaoInfo");
  if (info) info.style.display = "none";
  const input = document.getElementById("cadastro_original_id");
  if (input) input.value = "";
}

export function configurarIntegracaoReutilizacao() {
  window.limparReutilizacao = limparReutilizacao;

  window.selecionarCadastro = function (card) {
    document.getElementById("uname").value = card.dataset.nome || "";
    document.getElementById("cpf").value = card.dataset.cpf || "";
    document.getElementById("placa").value = card.dataset.placa || "";
    document.getElementById("mobileno").value = card.dataset.telefone || "";
    document.getElementById("email").value = card.dataset.email || "";
    document.getElementById("from").value = card.dataset.empresa || "";

    const photoPreview = document.getElementById("photoPreview");
    if (photoPreview && card.dataset.foto) {
      photoPreview.innerHTML = `<img src="${BASE_URL}index.php?page=api/get_photo&vid=${encodeURIComponent(card.dataset.id)}" alt="Foto" class="img-thumbnail" style="max-height:150px">`;
    }

    currentReusedId = card.dataset.id ? parseInt(card.dataset.id) : null;
    const info = document.getElementById("reutilizacaoInfo");
    if (info) info.style.display = "block";

    const modal = bootstrap.Modal.getInstance(document.getElementById("reutilizarModal"));
    if (modal) modal.hide();

    document.getElementById("meet")?.focus();
  };
}
