@php $canManage = auth()->user()->hasMenuAccess('admin.kampus.index'); @endphp
<a href="{{ route('admin.kampus.fakultas.index', $data) }}" class="btn btn-info btn-sm mr-1 mb-1" title="Kelola Fakultas">
  <i class="fas fa-building"></i>
</a>
@if($canManage)
<a href="{{ route('admin.kampus.ubah', $data) }}" class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah">
  <i class="fas fa-edit"></i>
</a>
<form action="{{ route('admin.kampus.hapus', $data) }}" method="POST" class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
  @csrf
  @method('DELETE')
  <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
    data-confirm-title="Hapus Kampus?"
    data-confirm-text="Apakah Anda yakin ingin menghapus '{{ $data->nama_kampus }}' beserta seluruh fakultas dan program studinya? Tindakan ini tidak dapat dibatalkan.">
    <i class="fas fa-trash"></i>
  </button>
</form>
@endif