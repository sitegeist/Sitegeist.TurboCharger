# Sitegeist.TurboCharger

Ensure preloaded caches by using a jobqueue to asynchronously request all published documents
to prefill all caches especially for routing, fusion and if installed full-page-caches.

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored
by http://www.sitegeist.de.*

## Installation 

By default the TurboCharger will use the fake-queue from `Flowpack.JobQueue.Common` but this will probably not work as
you expect as it will process all jobs directly after the data has been persisted. 

### Asynchronous warmup via `Flowpack.JobQueue.Doctrine`

1. Install a different job-queue implementation package that can persist the queue items like `Flowpack.JobQueue.Doctrine`.

```bash
composer require flowpack/jobqueue-doctrine
```
2. Configure the queue `sitegeist-turbocharger` to be handled by that package.

```yaml
Flowpack:
  JobQueue:
    Common:
      queues:
        'sitegeist-turbocharger':
          className: 'Flowpack\JobQueue\Doctrine\Queue\DoctrineQueue'
```
3. Setup the queue `sitegeist-turbocharger`

```bash
 ./flow queue:setup sitegeist-turbocharger
```

3. Run a worker `sitegeist-turbocharger`

```bash
 ./flow job:work --exit-after 3590 --queue sitegeist-turbocharger
```

## Configuration

```yaml
Sitegeist:
    TurboCharger:
        # enable the feature entirely
        enabled: true

        # Configure the url for fetching: the headers `Host` and `X-Forward-Proto` 
        # are used to simulate an external request. 
        # Use this to configure any special ports you may need like `http://localhost:8080`
        internalBaseUrl: 'http://localhost'
```

## Contribution

We will gladly accept contributions. Please send us pull requests.
