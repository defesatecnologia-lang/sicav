export function validarCPF(campo) {
  if (!campo) return false;
  let cpf = campo.value.replace(/\D/g, "");
  const errorEl = document.getElementById("cpfError");

  if (cpf.length !== 11) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    if (errorEl) errorEl.textContent = "CPF deve ter 11 dígitos";
    return false;
  }

  if (/^(\d)\1{10}$/.test(cpf)) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    if (errorEl) errorEl.textContent = "CPF inválido";
    return false;
  }

  let sum = 0;
  for (let i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
  let rest = (sum * 10) % 11;
  if (rest === 10) rest = 0;
  if (rest !== parseInt(cpf[9])) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    if (errorEl) errorEl.textContent = "CPF inválido";
    return false;
  }

  sum = 0;
  for (let i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
  rest = (sum * 10) % 11;
  if (rest === 10) rest = 0;
  if (rest !== parseInt(cpf[10])) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    if (errorEl) errorEl.textContent = "CPF inválido";
    return false;
  }

  campo.classList.remove("is-invalid");
  campo.classList.add("is-valid");
  if (errorEl) errorEl.textContent = "";
  return true;
}

export function validarPlaca(campo) {
  if (!campo) return true;
  let placa = campo.value.toUpperCase().replace(/-/g, "");

  const oldFormat = /^[A-Z]{3}\d{4}$/;
  const mercosul = /^[A-Z]{3}\d[A-Z]\d{2}$/;

  if (placa === "") return true;

  if (oldFormat.test(placa) || mercosul.test(placa)) {
    campo.classList.remove("is-invalid");
    campo.classList.add("is-valid");
    return true;
  }

  campo.classList.remove("is-valid");
  campo.classList.add("is-invalid");
  return false;
}
