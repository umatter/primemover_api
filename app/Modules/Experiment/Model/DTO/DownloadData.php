<?php

namespace App\Modules\Experiment\Model\DTO;

use App\Modules\Experiment\Model\Experiment;
use Spatie\LaravelData\Data;
use ZipArchive;

class DownloadData extends Data
{
    public ?string $pathUploadS3 = null;

    public ?string $crawlerFile = null;

    public ?string $queueFile = null;

    public ?string $jobFile = null;

    public ?string $behaviorFile = null;

    public ?string $zipFilePath = null;

    public ?string $folderPath = null;

    public ?string $finalArchiveName = null;

    public ?Experiment $experiment = null;

    public ?ZipArchive $zipArchive = null;

    public function getPathUploadS3(): ?string
    {
        return $this->pathUploadS3;
    }

    public function setPathUploadS3(?string $pathUploadS3): DownloadData
    {
        $this->pathUploadS3 = $pathUploadS3;

        return $this;
    }

    public function getCrawlerFile(): ?string
    {
        return $this->crawlerFile;
    }

    public function setCrawlerFile(?string $crawlerFile): DownloadData
    {
        $this->crawlerFile = $crawlerFile;

        return $this;
    }

    public function getQueueFile(): ?string
    {
        return $this->queueFile;
    }

    public function setQueueFile(?string $queueFile): DownloadData
    {
        $this->queueFile = $queueFile;

        return $this;
    }

    public function getJobFile(): ?string
    {
        return $this->jobFile;
    }

    public function setJobFile(?string $jobFile): DownloadData
    {
        $this->jobFile = $jobFile;

        return $this;
    }

    public function getBehaviorFile(): ?string
    {
        return $this->behaviorFile;
    }

    public function setBehaviorFile(?string $behaviorFile): DownloadData
    {
        $this->behaviorFile = $behaviorFile;

        return $this;
    }

    public function getZipFilePath(): ?string
    {
        return $this->zipFilePath;
    }

    public function setZipFilePath(?string $zipFilePath): DownloadData
    {
        $this->zipFilePath = $zipFilePath;

        return $this;
    }

    public function getFolderPath(): ?string
    {
        return $this->folderPath;
    }

    public function setFolderPath(?string $folderPath): DownloadData
    {
        $this->folderPath = $folderPath;

        return $this;
    }

    public function getFinalArchiveName(): ?string
    {
        return $this->finalArchiveName;
    }

    public function setFinalArchiveName(?string $finalArchiveName): DownloadData
    {
        $this->finalArchiveName = $finalArchiveName;

        return $this;
    }

    public function getExperiment(): ?Experiment
    {
        return $this->experiment;
    }

    public function setExperiment(?Experiment $experiment): DownloadData
    {
        $this->experiment = $experiment;

        return $this;
    }

    public function getZipArchive(): ?ZipArchive
    {
        return $this->zipArchive;
    }

    public function setZipArchive(?ZipArchive $zipArchive): DownloadData
    {
        $this->zipArchive = $zipArchive;

        return $this;
    }

    public function getMorphClass(): string
    {
        return 'DownloadData';
    }
}
