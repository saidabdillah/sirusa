@php $canManage = auth()->user()->hasMenuAccess('admin.kampus.index'); @endphp
<a href="{{ route('admin.kampus.prodi.index', [$kampus, $data]) }}" class="btn btn-info btn-sm mr-1 mb-1" title="Kelola Program Studi">
  <i class="fas fa-graduation-cap"></i>
</a>
@if($canManage)
<a href="{{ route('admin.kampus.fakultas.ubah', [$kampus, $data]) }}" class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah">
  <i class="fas fa-edit"></i>
</a>
<form action="{{ route('admin.kampus.fakultas.hapus', [$kampus, $data]) }}" method="POST" class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
  @csrf
  @method('DELETE')
  <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
    data-confirm-title="Hapus Fakultas?"
    data-confirm-text="Apakah Anda yakin ingin menghapus fakultas '{{ $data->nama }}' beserta seluruh program studinya? Tindakan ini tidak dapat dibatalkan.">
    <i class="fas fa-trash"></i>
  </button>
</form>
@endif