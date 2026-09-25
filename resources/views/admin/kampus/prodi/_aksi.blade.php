@if(auth()->user()->hasMenuAccess('admin.kampus.index'))
<a href="{{ route('admin.kampus.prodi.ubah', [$kampus, $fakultas, $data]) }}"
  class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah">
  <i class="fas fa-edit"></i>
</a>
<form action="{{ route('admin.kampus.prodi.hapus', [$kampus, $fakultas, $data]) }}" method="POST"
  class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
  @csrf
  @method('DELETE')
  <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
    data-confirm-title="Hapus Program Studi?"
    data-confirm-text="Apakah Anda yakin ingin menghapus program studi '{{ $data->nama }}'? Tindakan ini tidak dapat dibatalkan.">
    <i class="fas fa-trash"></i>
  </button>
</form>
@endif