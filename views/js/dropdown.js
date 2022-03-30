$(document).ready(function() {
  pk_change_dropdowns();

  $(document).on("click", ".pk_dropdown_view", function() {
    this.classList.toggle("opened");
  });

  $(document).on("change", ".pk_dropdown", function() {
    pk_build_dropdown(this.id);
  });

  $(document).on("click", ".pk_dropdown_view .option", function() {
    var dropdown = pk_get_parent_by_class(this, "pk_dropdown_view");
    var org_dropdown_id = dropdown.id.replace("-view", "");
    document.getElementById(org_dropdown_id).value = this.dataset.value;
    document.getElementById(org_dropdown_id).onchange();
    pk_build_dropdown(org_dropdown_id);
  });
});

function pk_change_dropdowns() {
  var all_dropdowns = document.querySelectorAll(".pk_dropdown");
  for (var i=0; i<all_dropdowns.length; i++) {
    pk_build_dropdown(all_dropdowns[i].id);
  }
}

function pk_build_dropdown(dropdown_id) {
  var org_dropdown = document.getElementById(dropdown_id);
  var params_names = org_dropdown.dataset.params.split(",");

  if (document.contains(document.getElementById(dropdown_id + "-view"))) {
    document.getElementById(dropdown_id + "-view").remove();
  }

  var container = document.createElement("div");
  container.setAttribute("id", dropdown_id + "-view");
  container.classList.add("pk_dropdown_view");
  container.classList.add("noselect");

  var main = document.createElement("div");
  main.classList.add("main");

  var text = document.createElement("span");
  text.innerHTML = org_dropdown.options[org_dropdown.selectedIndex].text;
  main.appendChild(text);  

  var list = document.createElement("div");
  list.classList.add("list");

  var list_empty = false;
  if (org_dropdown.options.length === 0 || (org_dropdown.options.length === 1 && org_dropdown.options[0].value === "")) {
    list_empty = true;
  }

  if (!list_empty) {
    for (var i=0; i<org_dropdown.options.length; i++) {
      var option = document.createElement("div");
      option.classList.add("option");
      if (org_dropdown.value === org_dropdown.options[i].value) {
        option.classList.add("selected");
      }
      option.dataset.value = org_dropdown.options[i].value;

      if (org_dropdown.options[i].value === "") {
        var span = document.createElement("span");
        span.classList.add("option-name");
        span.innerHTML = org_dropdown.options[i].text;
        option.appendChild(span);
        option.classList.add("empty");
      } else {
        for (var j=0; j<params_names.length; j++) {
          var span = document.createElement("span");
          span.classList.add("option-" + params_names[j]);
          span.innerHTML = org_dropdown.options[i].getAttribute("data-" + params_names[j]);

          option.appendChild(span);
        }
      }

      list.appendChild(option);
    }
  }

  container.appendChild(main);
  container.appendChild(list);
  org_dropdown.parentNode.insertBefore(container, org_dropdown.nextSibling);
  org_dropdown.style.display = 'none';
}

function pk_get_parent_by_class(el, cls) {
  while ((el = el.parentElement) && !el.classList.contains(cls));
  return el;
}
