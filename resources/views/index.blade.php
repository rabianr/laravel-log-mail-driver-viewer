<!doctype html>
<html lang="{{ config('app.locale') }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Log Mail Viewer') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
  </head>
  <body>
    <nav aria-label="Page navigation example" class="m-4 mb-3">
      <ul class="pagination">
        <li class="page-item @if (! $prevCr) disabled @endif">
          <a class="page-link" href="{{ route('logmailviewer.index', [ 'cr' => $prevCr ]) }}" >
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512" style="height: 1.5em;">
              <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M328 112L184 256l144 144"/>
            </svg>
          </a>
        </li>
        <li class="page-item @if (! $nextCr) disabled @endif">
          <a class="page-link" href="{{ route('logmailviewer.index', [ 'cr' => $nextCr ]) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512" style="height: 1.5em;">
              <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M184 112l144 144-144 144"/>
            </svg>
          </a>
        </li>
      </ul>
    </nav>

    <div class="list-group list-group-flush mx-4">
      <a class="list-group-item p-3">
        <div class="d-flex w-100">
          <strong class="text-body w-50">{{ __('Subject') }}</strong>
          <strong class="text-body w-25">{{ __('To') }}</strong>
          <strong class="text-body w-25">{{ __('Date') }}</strong>
        </div>
      </a>

      @foreach ($mails as $mail)
        <a href="#" class="list-group-item list-group-item-action p-3" data-bs-toggle="offcanvas"
          data-bs-target="#offcanvas-{{ $loop->index }}">
          <div class="d-flex w-100">
            <span class="w-50 text-truncate pe-4">{{ $mail->getHeaderValue('Subject') }}</span>
            <span class="w-25">
              {{ $mail->getHeader('To')->getPersonName() ?: $mail->getHeader('To')->getEmail() }}
            </span>
            <span class="w-25">
              {{ (new Carbon\Carbon($mail->getHeader('Date')->getRawValue()))->translatedFormat(__('D, j M Y H:i:s O')) }}
            </span>
          </div>
        </a>
      @endforeach

      @if (! $mails)
        <a class="list-group-item p-3">
          <div>
            {{ __('No Mail Found') }}
          </div>
        </a>
      @endif
    </div>

    @foreach ($mails as $mail)
      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-{{ $loop->index }}" style="--bs-offcanvas-width: 55%">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title">&nbsp;</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <h4 class="fw-bold">{{ $mail->getHeaderValue('Subject') }}</h4>
          <div class="d-flex flex-wrap mb-3">
            <div class="w-50">
                <div>
                  <strong>{{ __('From:') }}</strong>
                  {{ $mail->getHeader('From')->getRawValue() }}
                </div>
                <div>
                  <strong>{{ __('Date:') }}</strong>
                  {{ (new Carbon\Carbon($mail->getHeader('Date')->getRawValue()))->translatedFormat(__('D, j M Y H:i:s O')) }}
                </div>

                @if ($atts = $mail->getAllAttachmentParts())
                  <div class="d-flex">
                    <strong>{{ __('Attachments:') }}</strong>
                    <span class="ms-1">
                      {!! implode(',<br>', array_map(fn ($att) =>
                        $att->getHeaderParameter('Content-Disposition', 'filename')
                        . ' (' . number_format(strlen($att->getContent()) / 1024 / 1024, 2) . 'MB)', $atts)) !!}
                    </span>
                  </div>
                @endif
            </div>
            <div class="w-50">
              <div class="d-flex">
                <strong>{{ __('To:') }}</strong>
                <span class="ms-1">
                  {!! implode(',<br>', $getMailboxes($mail->getHeader('To'))) !!}
                </span>
              </div>

              @if ($cc = $mail->getHeader('Cc'))
                <div class="d-flex">
                  <strong>{{ __('Cc:') }}</strong>
                  <span class="ms-1">
                    {!! implode(',<br>', $getMailboxes($cc)) !!}
                  </span>
                </div>
              @endif

              @if ($bcc = $mail->getHeader('Bcc'))
                <div class="d-flex">
                  <strong>{{ __('Bcc:') }}</strong>
                  <span class="ms-1">
                    {!! implode(',<br>', $getMailboxes($bcc)) !!}
                  </span>
                </div>
              @endif

              @if ($replyTo = $mail->getHeader('Reply-To'))
                <div class="d-flex">
                  <strong>{{ __('Reply-To:') }}</strong>
                  <span class="ms-1">
                    {!! implode(',<br>', $getMailboxes($replyTo)) !!}
                  </span>
                </div>
              @endif
            </div>
          </div>

          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link @if ($html = $mail->getHtmlContent()) active @endif"
                id="html-tab-{{ $loop->index }}" data-bs-toggle="tab"
                data-bs-target="#html-tab-{{ $loop->index }}-pane" type="button" role="tab"
                @disabled(! $html)
              >
                HTML
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link @if (($text = $mail->getTextContent()) && ! $html) active @endif"
                id="text-tab-{{ $loop->index }}" data-bs-toggle="tab"
                data-bs-target="#text-tab-{{ $loop->index }}-pane" type="button" role="tab"
                @disabled(! $text)
              >
                {{ __('Text') }}
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link @if (! $html && ! $text) active @endif"
                id="raw-tab-{{ $loop->index }}" data-bs-toggle="tab"
                data-bs-target="#raw-tab-{{ $loop->index }}-pane" type="button" role="tab"
              >
                Raw
              </button>
            </li>
          </ul>
          <div class="tab-content pt-3 pb-4">
            <div class="tab-pane fade @if ($html) show active @endif"
              id="html-tab-{{ $loop->index }}-pane" role="tabpanel" tabindex="0"
            >
              @if ($html)
                <iframe srcdoc="{{ $html }}" style="width: 100%; border: 0; overflow:hidden;" sandbox="allow-same-origin"></iframe>
              @endif
            </div>
            <div class="tab-pane fade @if (! $html && $text) show active @endif"
              id="text-tab-{{ $loop->index }}-pane" role="tabpanel" tabindex="0"
            >
              {!! nl2br($mail->getTextContent(), false) !!}
            </div>
            <div class="tab-pane fade @if (! $html && ! $text) show active @endif"
              id="raw-tab-{{ $loop->index }}-pane" role="tabpanel" tabindex="0">
              {!! nl2br(htmlentities($mail->__toString()), false) !!}
            </div>
          </div>
        </div>
      </div>
    @endforeach

    <nav aria-label="Page navigation example" class="m-4 mb-3">
      <ul class="pagination">
        <li class="page-item @if (! $prevCr) disabled @endif">
          <a class="page-link" href="{{ route('logmailviewer.index', [ 'cr' => $prevCr ]) }}" >
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512" style="height: 1.5em;">
              <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M328 112L184 256l144 144"/>
            </svg>
          </a>
        </li>
        <li class="page-item @if (! $nextCr) disabled @endif">
          <a class="page-link" href="{{ route('logmailviewer.index', [ 'cr' => $nextCr ]) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512" style="height: 1.5em;">
              <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M184 112l144 144-144 144"/>
            </svg>
          </a>
        </li>
      </ul>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <script>
    {
      function resizeIframe(obj) {
        obj.style.height = obj.contentWindow.document.documentElement.scrollHeight + 'px';
      }

      document.querySelectorAll('.offcanvas').forEach(collapseEl => {
        collapseEl.addEventListener('shown.bs.offcanvas', event => {
          const ifr = event.target?.querySelector('iframe')
          ifr && resizeIframe(ifr)
        })
      })

      document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', event => {
          const ifr = document.querySelector(event.target.dataset.bsTarget).querySelector('iframe')
          ifr &&  resizeIframe(ifr)
        })
      })

      const ifr = document
        .querySelector(document.querySelector('button.active[data-bs-toggle="tab"]')?.dataset.bsTarget)
        ?.querySelector('iframe')
      ifr && setTimeout(() => resizeIframe(ifr))
    }
    </script>
  </body>
</html>
