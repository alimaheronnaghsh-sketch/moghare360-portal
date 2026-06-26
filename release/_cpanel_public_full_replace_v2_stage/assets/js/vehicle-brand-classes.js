/**
 * MOGHARE360 — Luxury vehicle brand → model/class mapping.
 */
window.M360_VEHICLE_BRANDS = {
  "بنز": ["C200", "C250", "C300", "E200", "E250", "E300", "E350", "S350", "S500", "S560", "GLC", "GLE", "GLS", "G-Class", "سایر"],
  "ب ام و": ["320", "325", "328", "330", "520", "523", "525", "528", "530", "730", "740", "X1", "X3", "X4", "X5", "X6", "سایر"],
  "پورشه": ["Cayenne", "Macan", "Panamera", "911", "Boxster", "Cayman", "سایر"],
  "ولوو": ["XC60", "XC90", "S60", "S80", "V40", "V60", "سایر"],
  "فولکس واگن": ["Passat", "Tiguan", "Touareg", "Golf", "Beetle", "Jetta", "سایر"],
  "سایر": ["سایر"]
};

window.m360PopulateVehicleBrands = function (brandSelect) {
  if (!brandSelect) return;
  brandSelect.innerHTML = '<option value="">انتخاب برند</option>';
  Object.keys(window.M360_VEHICLE_BRANDS).forEach(function (brand) {
    var opt = document.createElement('option');
    opt.value = brand;
    opt.textContent = brand;
    brandSelect.appendChild(opt);
  });
};

window.m360PopulateVehicleClasses = function (brandSelect, classSelect) {
  if (!brandSelect || !classSelect) return;
  var brand = brandSelect.value;
  classSelect.innerHTML = '<option value="">انتخاب کلاس / مدل</option>';
  if (!brand || !window.M360_VEHICLE_BRANDS[brand]) {
    classSelect.disabled = true;
    return;
  }
  classSelect.disabled = false;
  window.M360_VEHICLE_BRANDS[brand].forEach(function (cls) {
    var opt = document.createElement('option');
    opt.value = cls;
    opt.textContent = cls;
    classSelect.appendChild(opt);
  });
};
