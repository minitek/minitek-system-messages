(function (document, Joomla) {
  "use strict";

  var system_container;
  var options = Joomla.getOptions("miniteksystemmessages");
  var application_messages = options.application_messages;
  var error_text = options.error_text;
  var success_text = options.success_text;
  var notice_text = options.notice_text;
  var warning_text = options.warning_text;

  var polipop;
  var appendTo = options.appendTo;
  var position = options.position;
  var layout = options.layout;
  var theme = options.theme;
  var icons = options.icons;
  var insert = options.insert;
  var spacing = options.spacing;
  var pool = options.pool;
  var sticky = options.sticky;
  var life = options.life;
  var pauseOnHover = options.pauseOnHover;
  var headerText = options.headerText;
  var closer = options.closer;
  var closeText = options.closeText;
  var loadMoreText = options.loadMoreText;
  var effect = options.effect;
  var easing = options.easing;
  var effectDuration = options.effectDuration;

  // Create a new message
  function createMessage(content, type, issticky) {
    var title;
    var _type = "notice";
    var _sticky = issticky ? true : sticky;

    if (
      type === "error" ||
      type === "danger" ||
      type === "error alert-danger"
    ) {
      _type = "error";
      title = error_text;
    } else if (type === "message" || type === "success") {
      _type = "success";
      title = success_text;
    } else if (type === "warning") {
      _type = "warning";
      title = warning_text;
    } else {
      _type = "notice";
      title = notice_text;
    }

    // Create message
    polipop.add({
      type: _type,
      title: title,
      content: content,
      sticky: _sticky,
    });
  }

  // Create an observer instance - Observe the system-message-container for newly added messages
  function observeContainer() {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        var newNodes = mutation.addedNodes;

        // Τhere are new nodes
        if (newNodes !== null) {
          newNodes.forEach(function (
            node // node: <joomla-alert>
          ) {
            if (node.getAttribute("role") === "alert") {
              var node_type = node.getAttribute("type");
              var children = node.childNodes;

              children.forEach(function (child) {
                var content = child.innerHTML;

                if (child.classList.contains("alert-wrapper"))
                  createMessage(content, node_type);
              });

              system_container.style.display = "none";
              system_container.innerHTML = "";
            }
          });
        }
      });
    });

    // Configuration of the observer
    var config = {
      attributes: true,
      childList: true,
      characterData: true,
    };

    // Pass in the target node, as well as the observer options
    observer.observe(system_container, config);
  }

  document.addEventListener("DOMContentLoaded", function () {
    system_container = document.querySelector("#system-message-container");

    // Polipop options
    var polipopOptions = {
      appendTo: appendTo,
      position: position,
      layout: layout,
      theme: theme,
      icons: icons,
      insert: insert,
      spacing: spacing,
      pool: pool,
      sticky: sticky,
      life: life,
      progressbar: false,
      pauseOnHover: pauseOnHover,
      headerText: headerText,
      closer: closer,
      closeText: closeText,
      loadMoreText: loadMoreText,
      hideEmpty: true,
      effect: effect,
      easing: easing,
      effectDuration: effectDuration,
    };

    // Initialize Polipop
    polipop = new Polipop("polipop", polipopOptions);

    // Parse application messages
    if (application_messages.length) {
      system_container.style.display = "none";
      system_container.innerHTML = "";

      application_messages.forEach(function (element) {
        createMessage(element.message, element.type);
      });
    }

    // Observe container
    observeContainer();
  });
})(document, Joomla);
