@php
    /** @var \App\Models\HeroSlide $record */
    $record = $getRecord();
    $img = $record->image_url;
    $file = $record->video_url;
    $embed = \App\Services\VideoLinkParser::parse($record->video_link);
    $linkThumb = $embed ? \App\Services\VideoLinkParser::thumbnailUrl($embed['type'], $embed['id']) : null;
    $isDrive = ! $embed && \App\Services\VideoLinkParser::driveId($record->video_link);
    $hasVideo = $file || $embed || $isDrive;
    $label = $file ? 'Video' : ($embed ? ucfirst($embed['type']) : ($isDrive ? 'Drive' : null));
@endphp
<div style="position:relative;width:112px;height:63px;border-radius:6px;overflow:hidden;background:#2b2622;flex:none">
    @if($file)
        {{-- uploaded video: show its first frame (picture used as poster when there is one) --}}
        <video src="{{ $file }}#t=0.5" @if($img) poster="{{ $img }}" @endif muted playsinline preload="metadata"
               style="width:100%;height:100%;object-fit:cover;display:block"></video>
    @elseif($img || $linkThumb)
        <img src="{{ $img ?: $linkThumb }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block" />
    @elseif($isDrive)
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#e8dccb;font-size:11px">Google Drive</div>
    @else
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#b5a99b;font-size:11px">No media</div>
    @endif

    @if($hasVideo)
        <span style="position:absolute;left:4px;bottom:4px;display:inline-flex;align-items:center;gap:3px;padding:2px 6px;border-radius:999px;background:#C9A24A;color:#fff;font-size:10px;font-weight:600;line-height:1.4">
            <svg width="8" height="8" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M2 1l7 4-7 4z"/></svg>{{ $label }}
        </span>
    @endif
</div>
