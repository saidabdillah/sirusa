/**
 *
 * Global UI helpers for all forms:
 * - Automatically disables submit buttons and shows a spinner while submitting.
 * - SweetAlert2 loading dialog shown right before a delete/update form is submitted.
 * - Generic handler for the `.btn-delete` delete button (used by list pages).
 * - AJAX submit for any `form[data-ajax-form]`; no per-view wiring needed.
 *
 * Keep shared logic here instead of re-implementing it per view.
 *
 */

"use strict";

$(function () {
  // ---------------------------------------------------------------------
  // Satu standar loading untuk seluruh aplikasi.
  //
  // Dua "-loading" yang berbeda ini sengaja dipisah:
  //  1. Spinner di dalam tombol submit (`submitWithSpinner` di bawah) --
  //     dipakai form biasa dan form AJAX.
  //  2. Dialog SweetAlert `showSubmitLoading()` -- hanya untuk aksi
  //     destruktif (hapus / ubah status) yang butuh konfirmasi.
  //
  // Yang keduadulu tidak pernah ditutup kalau AJAX-nya berakhir dengan 422
  // yang errornya digambar inline, karena jalur itu `return` lebih dulu tanpa
  // memanggil `Swal.fire()` -- dan `Swal.fire` baru yang menutup dialog
  // sebelumnya. Akibatnya user terkunci di modal loading yang tidak punya
  // tombol tutup. Setiap jalur yang menyentuh request wajib memanggil
  // `closeSubmitLoading()`.
  // ---------------------------------------------------------------------

  // Dialog loading SweetAlert sedang terbuka? SweetAlert tidak mengekspos
  // state-nya, jadi kita tandai sendiri lewat flag ini.
  var submitLoadingOpen = false;

  window.showSubmitLoading = function (message) {
    submitLoadingOpen = true;

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

  /**
   * Tutup dialog loading kalau masih terbuka. Aman dipanggil berkali-kali,
   * dan aman dipanggil saat tidak ada dialog sama sekali.
   */
  window.closeSubmitLoading = function () {
    if (!submitLoadingOpen) {
      return;
    }

    submitLoadingOpen = false;
    Swal.close();
  };

  /**
   * Tombol yang mewakili "kirim" sebuah form.
   *
   * Default-nya `button[type="submit"]`. Beberapa form memakai
   * `type="button"` karena ada konfirmasi SweetAlert di tengah (mis.
   * "Simpan Keputusan" di halaman verifikasi), sehingga submit-nya
   * dipicu dari handler klik, bukan dari tombolnya sendiri. Tombol seperti
   * itu ditandai `data-loading-button` supaya tetap mendapat loading state
   * yang sama dengan tombol submit biasa -- kalau tidak, formnya jalan tanpa
   * umpan balik apa pun.
   *
   * @param {jQuery} $form
   * @returns {jQuery} koleksi tombol, mungkin kosong
   */
  function tombolSubmit($form) {
    var $submit = $form.find('button[type="submit"]').first();

    return $submit.length ? $submit : $form.find("[data-loading-button]").first();
  }

  // Disable submit buttons and show a spinner on any form submission
  $(document).on("submit", "form", function () {
    var $form = $(this);
    var $btn = tombolSubmit($form);

    if ($btn.length === 0 || $btn.prop("disabled")) {
      return;
    }

    submitWithSpinner($btn, $btn.data("loading-text") || "Menyimpan...");
    $form.find("a.btn").addClass("disabled");
  });

  /**
   * Pasang spinner di `$btn` dan kembalikan closure yang melepaskannya.
   * Satu tempat untuk "matikan" supaya tidak ada tombol yang luput di
   * keadaan `disabled + spinner`.
   *
   * HTML asli disimpan di `data-loading-html` supaya bisa dipulihkan lagi
   * nanti (jaring pengaman `pageshow`) tanpa harus menebak teks spinner-nya.
   *
   * Simpanannya TIDAK boleh ditimpa kalau sudah ada. Form `data-ajax-form`
   * dilalui dua handler submit: handler umum di atas (selector `form`) dan
   * handler delegasi `form[data-ajax-form]` yang memanggil `submitAjax()`.
   * Keduanya terikat di `document` dan terikat berurutan, jadi handler
   * delegation jalan setelah handler umum -- tanpa penjaga di sini, handler
   * kedua akan menimpa `data-loading-html` dengan HTML SPINNER, dan
   * `releaseSubmit()` memulihkan spinner sebagai "tombol asli". Tombolnya
   * lalu `[⟳ Menyimpan...]` selamanya, klik berikutnya tidak mengubah apa pun.
   *
   * @param {jQuery} $btn
   * @param {string} teks
   * @returns {function(): void}
   */
  function submitWithSpinner($btn, teks) {
    if (!$btn.data("loading-html")) {
      $btn.data("loading-html", $btn.html());
    }

    $btn
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin mr-1"></i>' + teks);

    return function releaseSubmit() {
      // Stash dibuang setelah dipulihkan supaya siklus request berikutnya
      // menebas ulang HTML yang benar-benar ada saat itu, bukan sisa dari
      // request sebelumnya.
      var html = $btn.data("loading-html");

      $btn
        .prop("disabled", false)
        .html(html)
        .removeData("loading-html");
    };
  }

  // ---------------------------------------------------------------------
  // AJAX form submit
  //
  // Bertumpu pada `form[data-ajax-form]` supaya halaman yang formnya masih
  // butuh full page (atau yang rely on `withInput()` untuk old value) tidak
  // ikut berubah diam-diam. Form multipart (profil) aman: FormData otomatis
  // membawa file ada/tidaknya, dan jQuery tetap harvests field biasa seperti
  // input biasa.
  // ---------------------------------------------------------------------

  // Pesan validasi Laravel: `field`, `field.0`, `field.*`. Blade merender
  // input multi (`prodi_ids[]`, `dokumen_prestasi[]`) dengan kurung siku, jadi
  // nama field harus dicocokkan dalam tiga tahap.
  function findField($form, name) {
    var $el = $form.find('[name="' + name + '"]');

    if (!$el.length) {
      $el = $form.find('[name="' + name + '[]"]');
    }

    if (!$el.length) {
      $el = $form.find('[name^="' + name + '["]');
    }

    // Tahap keempat untuk validasi per-index. Aturan `nama_kampus.*` melapor
    // error sebagai `nama_kampus.0`, sedangkan Blade merender repeater-nya
    // sebagai `nama_kampus[]` -- tidak ada satu pun dari tiga pola di atas
    // yang bisa menyentuhnya, sehingga error tiap baris tidak pernah digambar
    // di bawah input dan jatuh ke SweetAlert umum. Indeksnya dipakai untuk
    // menandai baris yang salah saja, bukan semua baris sekaligus.
    if (!$el.length) {
      var indeks = name.match(/\.(\d+)$/);

      if (indeks) {
        $el = $form
          .find('[name="' + name.replace(/\.\d+$/, "") + '[]"]')
          .eq(parseInt(indeks[1], 10));
      }
    }

    return $el;
  }

  /**
   * Samakan state `is-invalid` pada alt-input flatpickr.
   *
   * Flatpickr v4 menyembunyikan input aslinya (`setAttribute("type","hidden")`)
   * lalu menyisipkan `.flatpickr-alt` sebagai elemen yang benar-benar dilihat
   * pengguna. `altInputClass` hanya disalin SEKALI saat inisialisasi:
   * `altInputClass = el.className + " " + config.altInputClass`. Jadi error
   * yang sudah ada saat halaman dirender ikut tersalin dan tampil benar, tapi
   * error 422 dari AJAX datang jauh setelah inisialisasi -- `is-invalid` yang
   * kita pasang di input asli tidak akan pernah terlihat, dan yang tampil
   * hanya pesan errornya tanpa border merah.
   *
   * Alt-input adalah saudara langsung (sibling), bukan anak, jadi `.siblings()`
   * cukup. Kalau form sudah dibersihkan, alt-input ikut dibersihkan di
   * `clearFormErrors` secara terpisah karena tidak punya `aria-invalid`.
   *
   * @param {jQuery} $el input yang-found oleh findField
   */
  function cerminIsInvalid($el) {
    $el.siblings(".flatpickr-alt").toggleClass("is-invalid", $el.hasClass("is-invalid"));
  }

  function clearFormErrors($form) {
    $form.find(".is-invalid").removeClass("is-invalid").removeAttr("aria-invalid");
    $form.find(".flatpickr-alt").removeClass("is-invalid");
    $form.find(".invalid-feedback[data-ajax]").css("display", "none").text("");
  }

  function paintFieldError($form, name, message) {
    var $el = findField($form, name);

    if (!$el.length) {
      // Field-nya tidak ada di DOM (mis. blok wali yang sedang disembunyikan).
      // Rules-nya sendiri sudah menolak request, jadi cukup laporkan lewat Swal.
      return false;
    }

    var $group = $el.closest(".input-group");
    var $anchor = $group.length ? $group : $el;

    // Select2 menyisipkan kotak yang terlihat TEPAT setelah <select> aslinya
    // ($container.insertAfter(this.$element)), jadi "setelah <select>" adalah
    // DI ATAS kotak select2. Untuk field select2, sisipkan pesan setelah
    // .select2-container supaya tetap muncul di bawah input seperti semua
    // field lain. Input normal tetap memakai $anchor.after().
    var $after = $anchor;
    if ($anchor.is("select") && $anchor.hasClass("select2-hidden-accessible")) {
      var $container = $anchor.next(".select2-container");
      if ($container.length) {
        $after = $container;
      }
    }

    $el.addClass("is-invalid").attr("aria-invalid", "true");
    $group.addClass("is-invalid");
    cerminIsInvalid($el);

    var $feedback = $anchor.parent().children('.invalid-feedback[data-ajax]');

    if (!$feedback.length) {
      $feedback = $('<div>', {
        class: "invalid-feedback",
        "data-ajax": "1",
      });
      $after.after($feedback);
    }

    $feedback.text(message).css("display", "block");

    return true;
  }

  function firstMessage(errors) {
    for (var key in errors) {
      if (Object.prototype.hasOwnProperty.call(errors, key) && errors[key].length) {
        return errors[key][0];
      }
    }

    return null;
  }

  // Setelah simpan berhasil, halaman diperbarui secukupnya:
  //  - Halaman dengan DataTables server-side: tabelnya di-fetch ulang dari
  //    endpoint-nya sendiri, jadi tidak perlu reload.
  //  - Selain itu: navigasi ke `redirect` dari server (atau ke halaman sekarang
  //    kalau server tidak mengirim tujuan). Tabel yang dirender di sisi klien
  //    hanya bisa segar lewat pemuatan ulang, jadi sengaja dibiarkan begitu.
  function refreshAfterSave($form, payload) {
    var $serverSide = $("table").filter(function () {
      return $.fn.dataTable.isDataTable(this)
        && $(this).DataTable().settings()[0].oFeatures.bServerSide;
    });

    if ($serverSide.length) {
      $serverSide.each(function () {
        $(this).DataTable().ajax.reload(null, false);
      });

      $form[0].reset();
      clearFormErrors($form);

      if ($form.closest(".modal").length) {
        $form.closest(".modal").modal("hide");
      }

      return;
    }

    window.location.assign((payload && payload.redirect) || window.location.href);
  }

  function submitAjax($form, options) {
    var form = $form[0];
    var $btn = tombolSubmit($form);
    var releaseSubmit = submitWithSpinner($btn, $btn.data("loading-text") || "Menyimpan...");

    clearFormErrors($form);

    var body = new FormData(form);

    // Form method override. Kalau form sudah punya input `@method` (Blade),
    // FormData sudah membawanya -- jangan ditimpa jadi dua, karena
    // `$request->method()` akan menerima array dan routing gagal.
    if (!body.has("_method")) {
      body.append("_method", $form.data("method") || "POST");
    }

    $.ajax({
      url: $form.attr("action") || window.location.href,
      type: "POST",
      data: body,
      processData: false,
      contentType: false,
      dataType: "json",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      },
    })
      .done(function (payload) {
        if (payload && payload.success === false) {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: payload.message || "Data gagal disimpan.",
          });

          return;
        }

        // Standar success untuk seluruh aplikasi: alert muncul dulu, menutup
        // dirinya sendiri setelah 1500 ms, baru halaman disegarkan di dalam
        // `.then()`.
        //
        // `showConfirmButton: false` + `timer` berpasangan: tanpa timer, alert
        // menunggu klik OK dan user harus menekan dua kali untuk menyelesaikan
        // satu aksi. `refreshAfterSave()` harus tetap DI DALAM `.then()` -- kalau
        // dipindah ke luar, navigasi berjalan bersamaan dengan animasi alert
        // dan success-nya tidak pernah terlihat sama sekali.
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: (payload && payload.message) || "Data berhasil disimpan.",
          timer: 1500,
          showConfirmButton: false,
          timerProgressBar: true,
        }).then(function () {
          if (typeof options.onSuccess === "function") {
            options.onSuccess(payload, $form);
            return;
          }

          refreshAfterSave($form, payload);
        });
      })
      .fail(function (xhr) {
        var payload = xhr.responseJSON || {};

        // Jalur ini TIDAK BOLEH pernah memanggil `refreshAfterSave()` atau
        // menavigasi halaman. Error -- 422 validasi, 419 sesi kedaluwarsa, 403
        // akses, 500, atau koneksi putus (status 0) -- selalu harus meninggalkan
        // user di form yang sama supaya isinya tidak hilang dan supaya ia bisa
        // memperbaiki lalu mencoba lagi. Semua cabang di bawah hanya melukis
        // pesan dan `return`; pemulihan tombol terjadi di `.always()`.

        // 422 = validation error Laravel. TETAP digambar di bawah input,
        // bukan diganti SweetAlert, supaya user tahu field mana yang salah.
        if (xhr.status === 422 && payload.errors) {
          // Bersihkan sisa error dari request sebelumnya dulu, baru gambar
          // yang baru, supaya class merah tidak nyangkut di field yang sudah
          // diperbaiki.
          clearFormErrors($form);

          var painted = 0;

          $.each(payload.errors, function (name, messages) {
            if (paintFieldError($form, name, messages[0])) {
              painted += 1;
            }
          });

          if (painted === 0) {
            Swal.fire({
              icon: "error",
              title: "Validasi gagal",
              text: firstMessage(payload.errors) || "Periksa kembali data yang Anda isi.",
            });
          } else {
            var $first = $form.find(".is-invalid").first();

            if ($first.length) {
              $("html, body").animate({ scrollTop: $first.offset().top - 120 }, 200);
              $first.trigger("focus");
            }
          }

          return;
        }

        // 419 = CSRF kedaluwarsa, biasanya setelah tab ditinggalkan lama.
        // 403 = middleware akses.menu / otorisasi menolak.
        if (xhr.status === 419) {
          Swal.fire({
            icon: "warning",
            title: "Sesi kedaluwarsa",
            text: "Muat ulang halaman lalu coba lagi.",
          });

          return;
        }

        // status 0 = request tidak sampai ke server (koneksi putus, DNS, atau
        // diblokir browser). Server tidak pernah membalas, jadi pesan dari
        // `responseJSON` tidak ada -- sebelumnya user hanya melihat "Gagal"
        // tanpa penjelasan apa pun.
        if (xhr.status === 0) {
          Swal.fire({
            icon: "warning",
            title: "Koneksi bermasalah",
            text: "Permintaan tidak terkirim. Periksa koneksi internet Anda lalu coba lagi.",
          });

          return;
        }

        Swal.fire({
          icon: "error",
          title: xhr.status === 403 ? "Akses ditolak" : "Gagal",
          text: payload.message || "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.",
        });
      })
      // Satu-satunya tempat melepas spinner tombol + dialog loading. Dipasang
      // di `.always()`, bukan di dalam `.done()`/`.fail()`, supaya tombol
      // dijamin kembali normal pada SEMUA kondisi akhir: 2xx, 403, 404, 419,
      // 422, 500, dan koneksi putus (status 0).
      .always(function () {
        releaseSubmit();
        closeSubmitLoading();
      });
  }

  // Error lama harus hilang begitu user memperbaiki inputnya. Delegasi, bukan
  // bind per form, supaya form yang muncul belakangan (mis. partial di
  // `@stack('modal')`) ikut tercakup.
  $(document).on("input change", "form[data-ajax-form] input, form[data-ajax-form] select, form[data-ajax-form] textarea", function () {
    var $el = $(this);
    var name = $el.attr("name");

    if (!name) {
      return;
    }

    $el.removeClass("is-invalid").removeAttr("aria-invalid");
    $el.closest(".input-group").removeClass("is-invalid");
    $el.parent()
      .children('.invalid-feedback[data-ajax]')
      .css("display", "none")
      .text("");
  });

  // Handler tunggal untuk semua form CRUD. Delegasi ke `document` dipakai
  // supaya halaman cukup menandai formnya dengan `data-ajax-form` tanpa perlu
  // memanggil `ajaxForm()` di tiap view, dan agar urutan render
  // `@stack('script')` / `@stack('modal')` tidak berpengaruh.
  $(document).on("submit", "form[data-ajax-form]", function (e) {
    e.preventDefault();
    submitAjax($(this), {});
  });

  /**
   * Bentuk eksplisit untuk halaman yang butuh perilaku setelah sukses yang
   * berbeda, misalnya form profil yang memuat ulang halamannya sendiri.
   */
  window.ajaxForm = function (options) {
    var $form = $(options.formSelector);

    if (!$form.length) {
      return;
    }

    // Opt-out dari handler delegasi supaya tidak ada submit yang terkirim dua
    // kali kalau halaman ini juga memanggil fungsi ini.
    $form.removeAttr("data-ajax-form");
    $form.on("submit", function (e) {
      e.preventDefault();
      submitAjax($(this), options);
    });
  };

  // ---------------------------------------------------------------------
  // DataTable: satu bahasa loading untuk semua tabel
  //
  // Tanpa ini setiap tabel yang punya `processing: true` menampilkan
  // "Processing..." bahasa Inggris, sementara tabel yang tidak punya
  // `processing` sama sekali tidak menampilkan apa-apa saat memuat. Jadi
  // bisa terlihat satu tabel berbaca "Memproses..." dan yang lain diam.
  //
  // Setelan default di sini berlaku untuk semua tabel, termasuk yang diinisialisasi
  // di `@push('script')` view yang jalan lebih dulu.
  // ---------------------------------------------------------------------
  if ($.fn.dataTable) {
    $.fn.dataTable.defaults.oLanguage.sProcessing = "Memproses...";
  }

  // ---------------------------------------------------------------------
  // Date picker
  //
  // Semua halaman memakai flatpickr yang sudah dimuat global di layout, tapi
  // konfigurasinya berbeda-beda dan `ubah.blade.php` bahkan tidak punya
  // `disableMobile` -- di Android/iOS itu membuat flatpickr diganti date input
  // native, sehingga tampilannya beda antara satu form dan form lain.
  //
  // Nilai yang dikirim tetap `dateFormat` (Y-m-d, format kolom database);
  // `altFormat` cuma untuk yang dilihat user.
  // ---------------------------------------------------------------------

  window.initDatePicker = function (selector, options) {
    var settings = $.extend(
      {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        disableMobile: true,
        allowInput: false,
      },
      options || {}
    );

    return $(selector)
      .flatpickr(settings)
      .each(function () {
        // Nilai dari `old()` atau dari model bisa sudah terisi. flatpickr
        // membacanya sendiri, tapi `setDate` eksplisit menutup kasus di mana
        // elemen di-render ulang setelah inisialisasi sehingga inputnya kosong.
        if (this.value) {
          this._flatpickr.setDate(this.value, false);
        }
      });
  };

  /**
   * Jangan buka dialog loading kalau browser ternyata akan menahan submit
   * sendiri (validasi native HTML5: `required`, `pattern`, `minlength`, ...).
   *
   * Kalau tetap dibuka, dialognya tidak akan pernah tertutup: event `submit`
   * memang tidak terjadi, jadi tidak ada request yang bisa memanggil
   * `closeSubmitLoading()`, dan user terkunci di modal yang tidak punya tombol
   * tutup. Biarkan browser yang menampilkan bubble errornya.
   *
   * Form `data-ajax-form` dikecualikan: validasinya murni di server (lihat
   * `UpdateAccountRequest` dan form request lain), jadi `checkValidity()`
   * tidak relevan dan tidak boleh memblokir request.
   *
   * @param {jQuery} $form
   * @returns {boolean} true = jangan tampilkan dialog loading
   */
  function FormTahanLoading($form) {
    var form = $form[0];

    if ($form.is("[data-ajax-form]") || !form || typeof form.checkValidity !== "function") {
      return false;
    }

    return !form.checkValidity();
  }

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
      if (!result.isConfirmed || FormTahanLoading($form)) {
        return;
      }

      showSubmitLoading("Menghapus...");
      // `trigger('submit')`, bukan `submit()` native: form yang opted-in ke
      // AJAX harus tetap kena handler-nya, sedangkan `submit()` native
      // sengaja melewati semua handler jQuery.
      $form.trigger("submit");
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
      if (!result.isConfirmed || FormTahanLoading($form)) {
        return;
      }

      showSubmitLoading($btn.data("loading-text") || "Memproses...");
      $form.trigger("submit");
    });
  });

  // Bulk delete: checkbox selection + "Hapus Terpilih" button
  window.initMassDelete = function (options) {
    var $table = $(options.tableSelector);
    var $all = $(options.allSelector);
    var $btn = $(options.buttonSelector);
    var $form = $(options.formSelector);
    var label = options.entityLabel || "data";

    function getItems() {
      return $table.find(options.itemSelector);
    }

    function updateButton() {
      var $items = getItems();
      $btn.prop("disabled", $items.filter(":checked").length === 0);
      $all.prop("checked", $items.length > 0 && $items.filter(":checked").length === $items.length);
    }

    $table.on("change", options.itemSelector, function () {
      updateButton();
    });

    $all.on("change", function () {
      getItems().prop("checked", $all.prop("checked"));
      updateButton();
    });

    $btn.on("click", function () {
      var ids = getItems()
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
        if (!result.isConfirmed) {
          return;
        }

        // Form hapus massal selalu non-AJAX, jadi validasi native browser
        // bisa menahan submit. Cek dulu sebelum membuka dialog loading.
        if (FormTahanLoading($form)) {
          return;
        }

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
        $form.trigger("submit");
      });
    });
  };

  // ---------------------------------------------------------------------
  // Jaring pengaman terakhir: lepaskan semua spinner yang masih nyangkut.
  //
  // `pageshow` menyala juga saat halaman dipulihkan dari back/forward cache
  // (bfcache). Kalau user menekan tombol Simpan lalu langsung menekan "kembali"
  // sebelum request selesai, halaman di-cache dengan tombolnya masih
  // `disabled` + spinner, dan request-nya pun sudah dibatalkan. Tanpa baris
  // ini tombolnya tidak pernah bisa diklik lagi.
  // ---------------------------------------------------------------------
  window.addEventListener("pageshow", function (event) {
    if (!event.persisted) {
      return;
    }

    // Hanya tombol yang memang sedang pakai spinner. Jangan sentuh semua
    // `button[disabled]`: tombol seperti "Hapus Terpilih" memang dinonaktifkan
    // dari awal sampai ada baris yang dicentang, dan itu dikelola `initMassDelete`.
    $('form button[type="submit"][disabled], form [data-loading-button][disabled]').each(function () {
      var $this = $(this);

      // Tombol yang memang sedang pakai spinner. Jangan sentuh semua
      // `button[disabled]`: tombol seperti "Hapus Terpilih" memang
      // dinonaktifkan dari awal sampai ada baris yang dicentang, dan itu
      // dikelola `initMassDelete`.
      if (!this.hasAttribute("data-loading-html")) {
        return;
      }

      this.removeAttribute("disabled");
      this.innerHTML = $this.data("loading-html");
      $this.removeAttr("data-loading-html");
    });

    closeSubmitLoading();
  });
});