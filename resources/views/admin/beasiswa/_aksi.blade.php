@if(auth()->user()->hasMenuAccess('admin.beasiswa.index'))
<div class="d-flex">
  <a href="{{ route('admin.beasiswa.lihat', $data) }}" class="btn btn-info btn-sm mr-1 mb-1" title="Lihat">
    <i class="fas fa-eye"></i>
  </a>
  <a href="{{ route('admin.beasiswa.ubah', $data) }}" class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah">
    <i class="fas fa-edit"></i>
  </a>
  <form action="{{ route('admin.beasiswa.hapus', $data) }}" method="POST" class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus">
      <i class="fas fa-trash"></i>
    </button>
  </form>
</div>
@else
<a href="{{ route('admin.beasiswa.lihat', $data) }}" class="btn btn-info btn-sm">
  <i class="fas fa-eye"></i> Detail
</a>
@endif