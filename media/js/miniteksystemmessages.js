(function (document, Joomla) {
  "use strict";

  var system_container;
  var options = Joomla.getOptions("miniteksystemmessages");
  var token = options.token + "=1";
  var is_site = options.is_site;
  var site_path =
    is_site != true ? options.site_path + "administrator/" : options.site_path;
  var lifetime = options.lifetime;
  var user_id = options.user_id;
  var application_messages = options.application_messages;
  var session_message = options.session_message;
  var session_redirect_link = options.session_redirect_link;
  var actions_log = options.actions_log;
  var logged_users = options.logged_users;
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
  var progressbar = options.progressbar;
  var pauseOnHover = options.pauseOnHover;
  var headerText = options.headerText;
  var closer = options.closer;
  var closeText = options.closeText;
  var loadMoreText = options.loadMoreText;
  var hideEmpty = options.hideEmpty;
  var effect = options.effect;
  var easing = options.easing;
  var effectDuration = options.effectDuration;

  function HtmlToElement(htmlString) {
    var div = document.createElement("div");
    div.innerHTML = htmlString.trim();

    return div.firstChild;
  }

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

  // Check for expired user session
  function checkSession() {
    Joomla.request({
      url:
        site_path +
        "index.php?group=system&plugin=miniteksystemmessages&type=checkSession&" +
        token,
      method: "GET",
      onSuccess: (response, xhr) => {
        if (response) {
          var messages = JSON.parse(response);

          messages.forEach(function (element) {
            var content =
              '<div class="alert-message">' + element.message + "</div>";
            createMessage(content, element.type, true);
          });

          if (is_site && session_redirect_link)
            window.location.href = session_redirect_link;
        } else {
          lifetime = lifetime * 60000 + 1000;

          setTimeout(function () {
            checkSession();
          }, lifetime);
        }
      },
      onError: (xhr) => {
        console.log(xhr);
      },
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

  // Get actions log notifications via server-sent event
  function actionsLogEvent() {
    var es = new EventSource(
      site_path +
        "index.php?group=system&plugin=miniteksystemmessages&type=actionsLogEvent&" +
        token
    );

    var listener = function (event) {
      if (typeof event.data !== "undefined") {
        var content = JSON.parse(event.data).msg;
        content = '<div class="alert-message">' + content + "</div>";
        var type = JSON.parse(event.data).type;

        createMessage(content, type);
      }
    };

    es.addEventListener("open", listener);
    es.addEventListener("message", listener);
    es.addEventListener("error", listener);
  }

  // Get logged in users via server-sent event
  function loggedUsersEvent() {
    var es = new EventSource(
      site_path +
        "index.php?group=system&plugin=miniteksystemmessages&type=loggedUsersEvent&" +
        token
    );

    var listener = function (event) {
      if (typeof event.data !== "undefined") {
        var content = JSON.parse(event.data).msg;
        content = '<div class="alert-message">' + content + "</div>";
        var type = "info";

        createMessage(content, type);
      }
    };

    es.addEventListener("open", listener);
    es.addEventListener("message", listener);
    es.addEventListener("error", listener);
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
      progressbar: progressbar,
      pauseOnHover: pauseOnHover,
      headerText: headerText,
      closer: closer,
      closeText: closeText,
      loadMoreText: loadMoreText,
      hideEmpty: hideEmpty,
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

    // Session expiration message
    if (session_message && user_id > 0) checkSession();

    // Observe container
    observeContainer();

    // Actions log
    if (actions_log) actionsLogEvent();

    // Logged in users
    if (logged_users) loggedUsersEvent();
  });
})(document, Joomla);