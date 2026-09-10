/**
 *
 * Global UI helpers for all forms:
 * - Automatically disables submit buttons and shows a spinner while submitting.
 * - SweetAlert2 loading dialog shown right before a delete/update form is submitted.
 * - Generic handler for the `.btn-delete` delete button (used by list pages).
 *
 * Keep shared logic here instead of re-implementing it per view.
 *
 */

"use strict";

$(function () {
  // SweetAlert2 loading dialog shown before a form is submitted
  window.showSubmitLoading = function (message) {
    Swal.fire({
      title: message || "Memproses...",
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: function () {
        Swal.showLoading();
      },
    });
  };

  // Disable submit buttons and show a spinner on any form submission
  $(document).on("submit", "form", function () {
    var $btn = $(this).find('button[type="submit"]').first();

    if ($btn.length === 0 || $btn.prop("disabled")) {
      return;
    }

    $btn
      .prop("disabled", true)
      .html(
        '<i class="fas fa-spinner fa-spin mr-1"></i>' +
          ($btn.data("loading-text") || "Menyimpan...")
      );

    $(this).find("a.btn").addClass("disabled");
  });

  // Generic delete button inside a form (.btn-delete)
  $(document).on("click", ".btn-delete", function (e) {
    e.preventDefault();

    var $form = $(this).closest("form");

    Swal.fire({
      title: $(this).data("confirm-title") || "Hapus Data?",
      text:
        $(this).data("confirm-text") ||
        "Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#e74c3c",
      cancelButtonColor: "#6c757d",
      confirmButtonText: $(this).data("confirm-button") || "Ya, Hapus!",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (result.isConfirmed) {
        showSubmitLoading("Menghapus...");
        $form[0].submit();
      }
    });
  });

  // Generic status toggle button inside a form (.btn-confirm-toggle)
  $(document).on("click", ".btn-confirm-toggle", function (e) {
    e.preventDefault();

    var $form = $(this).closest("form");
    var $btn = $(this);

    Swal.fire({
      title: $btn.data("confirm-title") || "Ubah Status?",
      text: $btn.data("confirm-text") || "Apakah Anda yakin ingin mengubah status item ini?",
      icon: $btn.data("confirm-icon") || "warning",
      showCancelButton: true,
      confirmButtonColor: $btn.data("confirm-color") || "#e74c3c",
      cancelButtonColor: "#6c757d",
      confirmButtonText: $btn.data("confirm-button") || "Ya, Ubah!",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (result.isConfirmed) {
        showSubmitLoading($btn.data("loading-text") || "Memproses...");
        $form[0].submit();
      }
    });
  });

  // Bulk delete: checkbox selection + "Hapus Terpilih" button
  window.initMassDelete = function (options) {
    var $table = $(options.tableSelector);
    var $all = $(options.allSelector);
    var $items = $table.find(options.itemSelector);
    var $btn = $(options.buttonSelector);
    var $form = $(options.formSelector);
    var label = options.entityLabel || "data";

    function updateButton() {
      $btn.prop("disabled", $items.filter(":checked").length === 0);
      $all.prop("checked", $items.length > 0 && $items.filter(":checked").length === $items.length);
    }

    $table.on("change", options.itemSelector, function () {
      updateButton();
    });

    $all.on("change", function () {
      $items.prop("checked", $all.prop("checked"));
      updateButton();
    });

    $btn.on("click", function () {
      var ids = $items
        .filter(":checked")
        .map(function () {
          return $(this).val();
        })
        .get();
      var count = ids.length;

      if (count === 0) {
        return;
      }

      Swal.fire({
        title: "Hapus " + count + " " + label + "?",
        text:
          count + " " + label + " terpilih akan dihapus permanen. " +
          (options.confirmText || ""),
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e74c3c",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
      }).then(function (result) {
        if (result.isConfirmed) {
          $form.find('input[name="ids[]"]').remove();
          ids.forEach(function (id) {
            $form.append(
              $("<input>")
                .attr("type", "hidden")
                .attr("name", "ids[]")
                .val(id)
            );
          });
          showSubmitLoading("Menghapus...");
          $form[0].submit();
        }
      });
    });
  };
});