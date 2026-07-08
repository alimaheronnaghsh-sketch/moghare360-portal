/**
 * MOGHARE360 — Luxury vehicle brand → model/class mapping (PR-02A governance).
 */
window.M360_VEHICLE_BRANDS = {
  "بنز": ["C200", "C250", "C300", "E200", "E250", "E300", "E350", "S350", "S500", "S560", "GLC", "GLE", "GLS", "G-Class", "سایر"],
  "ب ام و": ["320", "325", "328", "330", "520", "523", "525", "528", "530", "730", "740", "X1", "X3", "X4", "X5", "X6", "سایر"],
  "پورشه": ["Cayenne", "Macan", "Panamera", "911", "Boxster", "Cayman", "سایر"],
  "ولوو": ["XC60", "XC90", "S60", "S80", "V40", "V60", "سایر"],
  "فولکس واگن": ["Passat", "Tiguan", "Touareg", "Golf", "Beetle", "Jetta", "سایر"],
  "سایر": []
};

window.M360_TOP_LEVEL_OTHER_BRAND = "سایر";
window.M360_MODEL_LIST_GAP_VALUE = "سایر";

window.m360IsTopLevelOtherBrand = function (brand) {
  return String(brand || "").trim() === window.M360_TOP_LEVEL_OTHER_BRAND;
};

window.m360IsModelListGap = function (brand, model) {
  if (window.m360IsTopLevelOtherBrand(brand)) {
    return false;
  }
  return String(model || "").trim() === window.M360_MODEL_LIST_GAP_VALUE;
};

window.m360PopulateVehicleBrands = function (brandSelect) {
  if (!brandSelect) return;
  brandSelect.innerHTML = '<option value="">انتخاب برند</option>';
  Object.keys(window.M360_VEHICLE_BRANDS).forEach(function (brand) {
    var opt = document.createElement("option");
    opt.value = brand;
    opt.textContent = brand;
    brandSelect.appendChild(opt);
  });
};

window.m360ResolveVehicleBrandKey = function (brand) {
  var raw = String(brand || "").trim();
  if (!raw || !window.M360_VEHICLE_BRANDS) {
    return raw;
  }
  if (Object.prototype.hasOwnProperty.call(window.M360_VEHICLE_BRANDS, raw)) {
    return raw;
  }
  var keys = Object.keys(window.M360_VEHICLE_BRANDS);
  for (var i = 0; i < keys.length; i++) {
    if (keys[i].trim() === raw) {
      return keys[i];
    }
  }
  return raw;
};

window.m360SyncVehicleClassRequired = function (brandSelect, classSelect) {
  if (!brandSelect || !classSelect) return;
  var brand = window.m360ResolveVehicleBrandKey(brandSelect.value);
  var needsClass = brand !== "" && !window.m360IsTopLevelOtherBrand(brand);
  if (needsClass && !classSelect.disabled) {
    classSelect.setAttribute("required", "required");
  } else {
    classSelect.removeAttribute("required");
  }
};

window.m360PopulateVehicleClasses = function (brandSelect, classSelect) {
  if (!brandSelect || !classSelect) return;
  var brand = window.m360ResolveVehicleBrandKey(brandSelect.value);
  classSelect.innerHTML = '<option value="">انتخاب کلاس / مدل</option>';
  if (!brand || !Object.prototype.hasOwnProperty.call(window.M360_VEHICLE_BRANDS, brand)) {
    classSelect.disabled = true;
    classSelect.removeAttribute("required");
    return;
  }
  if (window.m360IsTopLevelOtherBrand(brand)) {
    classSelect.disabled = true;
    classSelect.removeAttribute("required");
    classSelect.innerHTML = '<option value="">خارج از محدوده استاندارد — نیازمند بررسی مدیر</option>';
    return;
  }
  classSelect.disabled = false;
  window.M360_VEHICLE_BRANDS[brand].forEach(function (cls) {
    var opt = document.createElement("option");
    opt.value = cls;
    opt.textContent = cls;
    classSelect.appendChild(opt);
  });
  window.m360SyncVehicleClassRequired(brandSelect, classSelect);
};

window.m360SyncVehicleOtherPanels = function (brandSelect, classSelect, topOtherPanel, modelGapPanel) {
  var brand = brandSelect ? brandSelect.value : "";
  var model = classSelect ? classSelect.value : "";
  if (topOtherPanel) {
    topOtherPanel.style.display = window.m360IsTopLevelOtherBrand(brand) ? "block" : "none";
  }
  if (modelGapPanel) {
    modelGapPanel.style.display = window.m360IsModelListGap(brand, model) ? "block" : "none";
  }
};

window.m360BindVehicleSelector = function (opts) {
  opts = opts || {};
  var brandSelect = opts.brandSelect;
  var classSelect = opts.classSelect;
  var topOtherPanel = opts.topOtherPanel || null;
  var modelGapPanel = opts.modelGapPanel || null;
  if (!brandSelect || !classSelect) return;
  if (brandSelect.options.length <= 1) {
    window.m360PopulateVehicleBrands(brandSelect);
  }
  if (opts.initialBrand) {
    var resolvedBrand = window.m360ResolveVehicleBrandKey(opts.initialBrand);
    brandSelect.value = resolvedBrand;
    if (!brandSelect.value && resolvedBrand) {
      var matchOpt = Array.prototype.find.call(brandSelect.options, function (opt) {
        return window.m360ResolveVehicleBrandKey(opt.value) === resolvedBrand;
      });
      if (matchOpt) {
        brandSelect.value = matchOpt.value;
      }
    }
  }
  window.m360PopulateVehicleClasses(brandSelect, classSelect);
  if (opts.initialModel && !classSelect.disabled) {
    classSelect.value = opts.initialModel;
    if (!classSelect.value && opts.initialModel) {
      var matchModel = Array.prototype.find.call(classSelect.options, function (opt) {
        return String(opt.value).trim() === String(opts.initialModel).trim();
      });
      if (matchModel) {
        classSelect.value = matchModel.value;
      }
    }
  }
  window.m360SyncVehicleOtherPanels(brandSelect, classSelect, topOtherPanel, modelGapPanel);
  window.m360SyncVehicleClassRequired(brandSelect, classSelect);
  if (brandSelect.dataset.m360Bound === "1") {
    return;
  }
  brandSelect.dataset.m360Bound = "1";
  brandSelect.addEventListener("change", function () {
    window.m360PopulateVehicleClasses(brandSelect, classSelect);
    window.m360SyncVehicleOtherPanels(brandSelect, classSelect, topOtherPanel, modelGapPanel);
    window.m360SyncVehicleClassRequired(brandSelect, classSelect);
  });
  classSelect.addEventListener("change", function () {
    window.m360SyncVehicleOtherPanels(brandSelect, classSelect, topOtherPanel, modelGapPanel);
    window.m360SyncVehicleClassRequired(brandSelect, classSelect);
  });
};
